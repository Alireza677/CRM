<?php

namespace App\Services\Mail;

use App\Models\MailMessage;
use App\Models\MailFolder;
use App\Models\Mailbox;
use App\Models\UserNotificationSetting;
use App\Services\Notifications\NotificationRouter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Webklex\PHPIMAP\Address;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;

class MailSyncService
{
    public function __construct(
        protected ImapClient $imapClient
    ) {}

    public function syncInbox(Mailbox $mailbox): int
    {
        if (!$mailbox->is_active) {
            return 0;
        }

        $mailbox->loadMissing('user');

        $client = $this->imapClient->connect($mailbox);
        if (!$client) {
            return 0;
        }

        $folder = $mailbox->folders()->firstOrCreate(
            ['imap_path' => 'INBOX'],
            ['name' => 'INBOX']
        );

        $remoteFolder = $this->imapClient->getFolder($client, $folder->imap_path);
        if (!$remoteFolder) {
            Log::warning('[MAIL][SYNC] INBOX folder not found', ['mailbox_id' => $mailbox->id]);

            return 0;
        }

        $lastUid = $folder->last_uid;
        $query = $remoteFolder->messages()
            ->all()
            ->leaveUnread()
            ->setSequence(IMAP::ST_UID)
            ->setFetchBody(true)
            ->setFetchOrder('desc')
            ->setFetchFlags(true);

        if ($lastUid) {
            $query->whereUid(($lastUid + 1) . ':*');
        } else {
            $query->limit(100);
        }

        $messages = $query->get();
        if ($messages->isEmpty()) {
            $folder->forceFill(['last_sync_at' => now()])->save();
            $mailbox->forceFill(['last_sync_at' => now()])->save();

            return 0;
        }

        $imported = 0;
        $maxUid = $lastUid ?? 0;

        /** @var Message $message */
        foreach ($messages->sortBy('uid') as $message) {
            $imapUid = $message->getUid();
            $maxUid = max($maxUid, (int) $imapUid);

            $data = $this->mapMessage($mailbox, $folder, $message);

            $existing = null;
            if (!empty($data['message_id'])) {
                $existing = MailMessage::query()
                    ->where('mailbox_id', $mailbox->id)
                    ->where('message_id', $data['message_id'])
                    ->first();
            }

            $mailMessage = null;
            $wasCreated = false;
            if ($existing) {
                $existing->fill($data)->save();
                $mailMessage = $existing;
            } else {
                $mailMessage = MailMessage::updateOrCreate(
                    [
                        'folder_id'  => $folder->id,
                        'imap_uid'   => $imapUid,
                    ],
                    $data
                );
                $wasCreated = (bool) $mailMessage->wasRecentlyCreated;
            }

            if ($mailMessage && $wasCreated) {
                $this->notifyNewEmail($mailbox, $mailMessage);
            }
            $imported++;
        }

        $folder->forceFill([
            'last_uid'    => $maxUid ?: $folder->last_uid,
            'last_sync_at'=> now(),
        ])->save();

        $mailbox->forceFill(['last_sync_at' => now()])->save();

        return $imported;
    }

    public function hydrateMessageBody(MailMessage $message): void
    {
        $mailbox = $message->mailbox;
        $folder = $message->folder;

        if (!$mailbox || !$folder || !$mailbox->is_active) {
            return;
        }

        $client = $this->imapClient->connect($mailbox);
        if (!$client) {
            return;
        }

        $remoteFolder = $this->imapClient->getFolder($client, $folder->imap_path);
        if (!$remoteFolder) {
            return;
        }

        $remote = $remoteFolder->messages()
            ->setSequence(IMAP::ST_UID)
            ->setFetchBody(true)
            ->whereUid($message->imap_uid)
            ->get()
            ->first();

        if (!$remote) {
            return;
        }

        $traceId = (string) Str::uuid();
        $body = $this->extractBodyFromMessage($remote, $mailbox, $traceId, $message->imap_uid, $message->message_id);

        $message->forceFill([
            'body_text' => $body['text'] ?: null,
            'body_html' => $body['html'] ?: null,
            'snippet'   => $this->buildSnippetFromText($body['text'], $body['html']),
            'is_read'   => true,
        ])->save();
    }

    protected function notifyNewEmail(Mailbox $mailbox, MailMessage $message): void
    {
        $user = $mailbox->user;
        if (!$user) {
            return;
        }

        $emailSubject = $message->subject ?: 'ایمیل جدید دارید';
        $fromName = $message->from_name ?: ($message->from_email ?: '');
        $fromEmail = $message->from_email ?: '';

        static $enabledCache = [];
        $cacheKey = $user->id.':'.UserNotificationSetting::EMAIL_RECEIVED_KEY;
        if (!array_key_exists($cacheKey, $enabledCache)) {
            $enabledCache[$cacheKey] = UserNotificationSetting::getBool(
                $user->id,
                UserNotificationSetting::EMAIL_RECEIVED_KEY,
                true
            );
        }
        if (!$enabledCache[$cacheKey]) {
            return;
        }

        $url = null;
        try {
            $url = $message->thread_key
                ? route('mail.thread', ['thread_key' => $message->thread_key])
                : route('mail.show', ['message' => $message->id]);
        } catch (\Throwable $e) {
            // ignore URL resolution errors
        }

        $router = app(NotificationRouter::class);

        $context = [
            'actor'         => $user,
            'email_subject' => $emailSubject,
            'subject'       => $emailSubject,
            'from_name'     => $fromName,
            'from_email'    => $fromEmail,
            'received_at'   => $message->date ? $message->date->format('Y-m-d H:i') : '',
            'form_title'    => $emailSubject,
            'url'           => $url,
        ];

        try {
            $router->route('emails', 'received', $context, [$user]);
        } catch (\Throwable $e) {
            Log::warning('[MAIL][SYNC] failed to dispatch email.received notification', [
                'mailbox_id' => $mailbox->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function mapMessage(Mailbox $mailbox, MailFolder $folder, Message $message): array
    {
        $from = $this->firstAddress($message->getFrom()?->all() ?? []);
        $to = $this->addressesToArray($message->getTo()?->all() ?? []);
        $cc = $this->addressesToArray($message->getCc()?->all() ?? []);
        $inReplyTo = $this->decodeMimeHeader($this->attributeToString($message->getInReplyTo()));
        $referencesAttr = $message->getReferences();
        $references = $referencesAttr ? ($referencesAttr->toArray() ?? []) : [];
        if (is_string($references)) {
            $references = array_filter(array_map('trim', explode(' ', $references)));
        }

        $body = $this->extractBodyFromMessage($message, $mailbox);

        return [
            'mailbox_id' => $mailbox->id,
            'folder_id'  => $folder->id,
            'imap_uid'   => $message->getUid(),
            'message_id' => $this->attributeToString($message->getMessageId()),
            'thread_key' => $this->buildThreadKey(
                $this->attributeToString($message->getMessageId()),
                $inReplyTo,
                $references,
                $this->attributeToString($message->getSubject()),
                $from,
                $to
            ),
            'in_reply_to' => $inReplyTo,
            'references'  => $references ?: null,
            'subject'    => $this->decodeMimeHeader($this->attributeToString($message->getSubject())),
            'from_name'  => $from['name'] ?? null,
            'from_email' => $from['email'] ?? null,
            'to'         => $to,
            'cc'         => $cc,
            'date'       => $this->extractDate($message),
            'snippet'    => $this->buildSnippetFromText($body['text'], $body['html']),
            'is_read'    => $message->hasFlag('Seen'),
            'body_text'  => $body['text'] ?: null,
            'body_html'  => $body['html'] ?: null,
            'is_archived'=> false,
            'is_starred' => false,
            'is_deleted' => false,
        ];
    }

    protected function buildSnippetFromText(?string $textBody, ?string $htmlBody): ?string
    {
        $text = trim((string) $textBody);
        if ($text === '' && $htmlBody !== null) {
            $text = trim(strip_tags((string) $htmlBody));
        }

        if ($text === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', $text) ?? $text;

        return Str::limit($normalized, 200);
    }

    protected function addressesToArray(array $addresses): ?array
    {
        if (empty($addresses)) {
            return null;
        }

        return collect($addresses)
            ->filter(fn ($item) => $item instanceof Address)
            ->map(fn (Address $address) => [
                'name'  => $this->decodeMimeHeader($address->personal ?: null),
                'email' => $address->mail ?: null,
            ])
            ->values()
            ->all();
    }

    protected function firstAddress(array $addresses): ?array
    {
        $address = collect($addresses)->first(fn ($item) => $item instanceof Address);
        if (!$address instanceof Address) {
            return null;
        }

        return [
            'name'  => $this->decodeMimeHeader($address->personal ?: null),
            'email' => $address->mail ?: null,
        ];
    }

    protected function attributeToString($attribute): ?string
    {
        if ($attribute === null) {
            return null;
        }

        return (string) $attribute;
    }

    protected function decodeMimeHeader(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $decoded = $value;

        if (function_exists('iconv_mime_decode')) {
            $result = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($result !== false) {
                $decoded = $result;
            }
        } elseif (function_exists('mb_decode_mimeheader')) {
            $decoded = @mb_decode_mimeheader($value);
        }

        // Fallback for parts like =?UTF-8?B?...?=
        if ($decoded === $value && str_contains($value, '=?') && function_exists('imap_mime_header_decode')) {
            $decodedParts = @imap_mime_header_decode($value);
            if (is_array($decodedParts)) {
                $decoded = collect($decodedParts)->map(fn ($p) => $p->text ?? '')->implode('');
            }
        }

        return is_string($decoded) ? trim($decoded) : $value;
    }

    protected function extractBodyFromMessage(Message $message, ?Mailbox $mailbox = null, ?string $traceId = null, ?int $imapUid = null, ?string $messageId = null): array
    {
        // First try high-level accessors
        $textContent = trim((string) $message->getTextBody());
        $htmlContent = trim((string) $message->getHTMLBody());

        if ($textContent === '' && $htmlContent !== '') {
            $stripped = trim(strip_tags($htmlContent));
            if ($stripped !== '') {
                $textContent = $stripped;
            }
        }

        if ($textContent !== '' || $htmlContent !== '') {
            return [
                'text' => $textContent !== '' ? $textContent : null,
                'html' => $htmlContent !== '' ? $htmlContent : null,
            ];
        }

        // Deep traversal fallback
        $parts = [];
        if (method_exists($message, 'getParts')) {
            foreach ($message->getParts() as $part) {
                $parts[] = $this->normalizePart($part);
            }
        } elseif (method_exists($message, 'getBodies')) {
            foreach ($message->getBodies() as $bodyPart) {
                $parts[] = $this->normalizePart($bodyPart);
            }
        }

        $decodedParts = [];
        foreach ($parts as $part) {
            // skip attachments
            $disposition = strtolower((string) ($part['disposition'] ?? ''));
            if ($disposition === 'attachment') {
                continue;
            }

            $mime = strtolower((string) ($part['mime'] ?? ''));
            if (!str_starts_with($mime, 'text/plain') && !str_starts_with($mime, 'text/html')) {
                continue;
            }

            $raw = (string) ($part['raw'] ?? '');
            $encoding = $part['encoding'] ?? null;
            $charset = $part['charset'] ?? null;
            $decodedContent = $this->convertToUtf8(
                $this->decodeContent($raw, $encoding),
                $charset
            );

            $decodedParts[] = [
                'mime'        => $part['mime'] ?? null,
                'charset'     => $charset,
                'encoding'    => $encoding,
                'size'        => $part['size'] ?? null,
                'disposition' => $part['disposition'] ?? null,
                'type'        => $part['type'] ?? null,
                'subtype'     => $part['subtype'] ?? null,
                'content'     => $decodedContent,
                'content_len' => strlen($decodedContent),
            ];
        }

        $text = collect($decodedParts)
            ->first(fn ($p) => str_starts_with(strtolower((string) $p['mime']), 'text/plain') && trim((string) $p['content']) !== '');

        $html = collect($decodedParts)
            ->first(fn ($p) => str_starts_with(strtolower((string) $p['mime']), 'text/html') && trim((string) $p['content']) !== '');

        $textContent = $text['content'] ?? '';
        $htmlContent = $html['content'] ?? '';

        if ($textContent === '' && $htmlContent !== '') {
            $stripped = trim(strip_tags($htmlContent));
            if ($stripped !== '') {
                $textContent = $stripped;
            }
        }

        if ($textContent === '' && $htmlContent === '' && $mailbox && $traceId) {
            Log::channel('mail')->warning('mail.imap.body_missing', [
                'trace_id'   => $traceId,
                'mailbox_id' => $mailbox->id,
                'imap_uid'   => $imapUid,
                'message_id' => $messageId,
                'parts'      => collect($decodedParts)->map(fn ($p) => Arr::only($p, ['mime', 'charset', 'encoding', 'size', 'disposition', 'type', 'subtype', 'content_len']))->all(),
            ]);
        }

        return [
            'text' => $textContent !== '' ? $textContent : null,
            'html' => $htmlContent !== '' ? $htmlContent : null,
        ];
    }

    protected function normalizePart($part): array
    {
        $mime = null;
        $charset = null;
        $encoding = null;
        $size = null;
        $content = '';
        $disposition = null;
        $type = null;
        $subtype = null;

        if (is_object($part)) {
            $mime = $part->getMimeType() ?? ($part->type ?? null);
            $charset = $part->charset ?? ($part->getCharset() ?? null);
            $encoding = $part->encoding ?? ($part->getEncoding() ?? null);
            $size = $part->size ?? null;
            $disposition = $part->disposition ?? ($part->getDisposition() ?? null);
            $type = $part->type ?? null;
            $subtype = $part->subtype ?? null;
            if (method_exists($part, 'getContent')) {
                $content = (string) $part->getContent();
            } elseif (property_exists($part, 'content')) {
                $content = (string) $part->content;
            }
        } elseif (is_array($part)) {
            $mime = $part['mime'] ?? null;
            $charset = $part['charset'] ?? null;
            $encoding = $part['encoding'] ?? null;
            $size = $part['size'] ?? null;
            $content = (string) ($part['raw'] ?? '');
            $disposition = $part['disposition'] ?? null;
            $type = $part['type'] ?? null;
            $subtype = $part['subtype'] ?? null;
        }

        return [
            'mime'     => $mime ?: 'text/plain',
            'charset'  => $charset,
            'encoding' => $encoding,
            'size'     => $size,
            'raw'      => $content,
            'disposition' => $disposition,
            'type'        => $type,
            'subtype'     => $subtype,
        ];
    }

    protected function decodeContent(string $content, ?string $encoding): string
    {
        $encoding = strtolower((string) $encoding);
        return match ($encoding) {
            'base64', 'b', 'b64'        => base64_decode($content, true) ?: $content,
            'quoted-printable', 'q', 'quotedprintable' => quoted_printable_decode($content),
            default => $content,
        };
    }

    protected function convertToUtf8(string $content, ?string $charset): string
    {
        $charset = $charset ?: 'UTF-8';
        $charset = strtoupper($charset);

        if ($charset === 'UTF-8') {
            return $content;
        }

        try {
            $converted = @mb_convert_encoding($content, 'UTF-8', $charset);
            if ($converted !== false) {
                return $converted;
            }
        } catch (\Throwable) {
            // ignore
        }

        return $content;
    }

    protected function extractDate(Message $message): ?Carbon
    {
        $date = $message->getDate();
        $value = $date?->first() ?? null;

        try {
            if ($value instanceof Carbon) {
                return $value;
            }

            if ($value) {
                return Carbon::parse($value);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    protected function buildThreadKey(?string $messageId, ?string $inReplyTo, array $references, ?string $subject, ?array $from, ?array $to): string
    {
        if (!empty($references)) {
            $root = $references[0];
            return hash('sha256', $root);
        }

        if (!empty($inReplyTo)) {
            return hash('sha256', $inReplyTo);
        }

        if (!empty($messageId)) {
            return hash('sha256', $messageId);
        }

        $normalizedSubject = $this->normalizeSubject($subject);
        $participants = collect([$from['email'] ?? null])
            ->merge(collect($to)->pluck('email')->all())
            ->filter()
            ->unique()
            ->sort()
            ->implode(',');

        return hash('sha256', $normalizedSubject.'|'.$participants);
    }

    protected function normalizeSubject(?string $subject): string
    {
        if ($subject === null) {
            return '';
        }

        $s = trim($subject);
        $s = preg_replace('/^(re|fwd):\s*/i', '', $s);

        return mb_strtolower(trim($s ?? ''));
    }
}
