<?php

namespace App\Services\Mail;

use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Models\Mailbox;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailSendService
{
    protected string $attachmentDisk = 'private';

    private function addr(string $email, $name = null): Address
    {
        $email = trim($email);

        $name = is_string($name) ? trim($name) : '';
        if ($name === '') {
            return new Address($email);
        }

        return new Address($email, $name);
    }

    public function send(Mailbox $mailbox, array $payload, array $uploadedAttachments = [], ?string $traceId = null): ?MailMessage
    {
        $traceId = $traceId ?: (string) Str::uuid();
        $context = $this->baseContext($mailbox, $payload, $uploadedAttachments, $traceId);

        Log::channel('mail')->info('mail.send.start', $context + ['stage' => 'start']);

        if (!$mailbox->is_active) {
            Log::channel('mail')->warning('mail.send.aborted', $context + ['stage' => 'start', 'reason' => 'mailbox_inactive']);
            return null;
        }

        $mailer = $this->makeMailer($mailbox, $context);
        if (!$mailer) {
            return null;
        }

        try {
            $email = $this->buildEmail($mailbox, $payload, $uploadedAttachments);
        } catch (\Throwable $e) {
            Log::channel('mail')->error('mail.send.build_failed', $context + [
                'stage'     => 'build_email',
                'error'     => $e->getMessage(),
                'exception' => $this->exceptionContext($e),
            ]);
            return null;
        }

        try {
            Log::channel('mail')->info('mail.send.before_send', $context + ['stage' => 'before_send']);
            $mailer->send($email);
            Log::channel('mail')->info('mail.send.after_send', $context + ['stage' => 'after_send']);
        } catch (\Throwable $e) {
            Log::channel('mail')->error('mail.send.failed', $context + [
                'stage'    => 'after_send',
                'error'    => $e->getMessage(),
                'exception'=> $this->exceptionContext($e),
            ]);

            return null;
        }

        $message = $this->storeSentMessage($mailbox, $email, $payload, $uploadedAttachments);
        Log::channel('mail')->info('mail.send.store_sent_done', $context + [
            'stage' => 'store_sent_done',
            'stored_message_id' => $message->id ?? null,
        ]);

        return $message;
    }

    protected function makeMailer(Mailbox $mailbox, array $context = []): ?Mailer
    {
        $password = $mailbox->password;
        if (empty($password)) {
            Log::channel('mail')->warning('mail.transport.missing_password', $context + ['stage' => 'before_transport']);
            return null;
        }

        $host = $mailbox->smtp_host;
        $port = $mailbox->smtp_port ?: 587;
        $scheme = $mailbox->smtp_encryption === 'ssl' ? 'smtps' : 'smtp';

        $query = '';
        if ($mailbox->smtp_encryption === 'tls') {
            $query = '?encryption=tls';
        }

        $username = rawurlencode($mailbox->username ?: $mailbox->email_address);
        $pass     = rawurlencode($password);

        $dsn = "{$scheme}://{$username}:{$pass}@{$host}:{$port}{$query}";

        try {
            Log::channel('mail')->info('mail.transport.before_transport', $context + ['stage' => 'before_transport']);
            $transport = Transport::fromDsn($dsn);
            Log::channel('mail')->info('mail.transport.after_transport', $context + ['stage' => 'after_transport']);
            return new Mailer($transport);
        } catch (\Throwable $e) {
            Log::channel('mail')->error('mail.transport.invalid', $context + [
                'stage' => 'after_transport',
                'error' => $e->getMessage(),
                'exception' => $this->exceptionContext($e),
            ]);

            return null;
        }
    }

    protected function buildEmail(Mailbox $mailbox, array $payload, array $uploadedAttachments = []): Email
    {
        $email = new Email();

        $fromName = trim((string) ($mailbox->user?->name ?? ''));
        $email->from($fromName !== ''
            ? new Address($mailbox->email_address, $fromName)
            : new Address($mailbox->email_address)
        );
        $email->to(...$this->mapAddresses($payload['to']));

        if (!empty($payload['cc'])) {
            $email->cc(...$this->mapAddresses($payload['cc']));
        }

        if (!empty($payload['bcc'])) {
            $email->bcc(...$this->mapAddresses($payload['bcc']));
        }

        $email->subject($payload['subject'] ?? '');

        $bodyHtml = $payload['body_html'] ?? null;
        $bodyText = $payload['body_text'] ?? null;

        if (!empty($bodyHtml)) {
            $email->html($bodyHtml);
            if (!empty($bodyText)) {
                $email->text($bodyText);
            }
        } else {
            $email->text($bodyText ?? '');
        }

        $disk = Storage::disk($this->attachmentDisk);
        $shouldAttachData = $this->shouldAttachAsData();

        foreach ($uploadedAttachments as $attachment) {
            $relativePath = $attachment['storage_path'] ?? null;
            $filename = $attachment['filename'] ?? ($relativePath ? basename($relativePath) : 'attachment');
            $mime = $attachment['mime'] ?? null;

            if (empty($relativePath)) {
                Log::channel('mail')->error('mail.send.attachment_missing_path', [
                    'disk' => $this->attachmentDisk,
                    'attachment' => $attachment,
                ]);
                throw new \RuntimeException('Attachment path is missing.');
            }

            if (!$disk->exists($relativePath)) {
                Log::channel('mail')->error('mail.send.attachment_not_found', [
                    'disk' => $this->attachmentDisk,
                    'relative_path' => $relativePath,
                ]);
                throw new \RuntimeException("Attachment not found on disk {$this->attachmentDisk}: {$relativePath}");
            }

            $fullPath = $disk->path($relativePath);

            if ($shouldAttachData) {
                $email->attach(
                    $disk->get($relativePath),
                    $filename,
                    $mime
                );
            } else {
                $email->attachFromPath(
                    $fullPath,
                    $filename,
                    $mime
                );
            }
        }

        $messageId = $payload['message_id'] ?? null;
        if (empty($messageId)) {
            $messageId = sprintf('<%s@%s>', Str::uuid(), parse_url(config('app.url'), PHP_URL_HOST) ?: 'local');
        }

        $headers = $email->getHeaders();

        // اگر قبلاً وجود داشت حذفش کن
        if ($headers->has('Message-ID')) {
            $headers->remove('Message-ID');
        }

        // درستش: IdentificationHeader
        $headers->addIdHeader('Message-ID', trim((string)$messageId, '<>'));
        if (!empty($payload['in_reply_to'])) {
            $headers->addIdHeader('In-Reply-To', trim((string)$payload['in_reply_to'], '<>'));
        }

        if (!empty($payload['references']) && is_array($payload['references'])) {
            // بهتره یک header references با چند id ساخته بشه، ولی این هم امن و قابل قبوله:
            foreach ($payload['references'] as $ref) {
                $ref = trim((string)$ref);
                if ($ref !== '') {
                    $headers->addIdHeader('References', trim($ref, '<>'));
                }
            }
        }


        return $email;
    }

   protected function mapAddresses(array $list): array
{
    return collect($list)
        ->filter()
        ->map(function ($item) {

            $email = is_array($item)
                ? trim((string) ($item['email'] ?? ''))
                : trim((string) $item);

            if ($email === '') {
                return null;
            }

            $name = is_array($item)
                ? trim((string) ($item['name'] ?? ''))
                : '';

            // اگر name خالی است، Address بدون name بساز
            if ($name === '') {
                return new Address($email);
            }

            return new Address($email, $name);
        })
        ->filter()   // null ها را حذف کن
        ->values()
        ->all();
    }


    protected function storeSentMessage(Mailbox $mailbox, Email $email, array $payload, array $uploadedAttachments = []): MailMessage
    {
        $sentFolder = $mailbox->folders()->firstOrCreate(
            ['imap_path' => 'Sent'],
            ['name' => 'Sent']
        );

        $messageIdHeader = $email->getHeaders()->get('Message-ID');
        $messageId = $messageIdHeader ? $messageIdHeader->getBodyAsString() : null;

        $toList = $payload['to'] ?? [];
        $ccList = $payload['cc'] ?? [];

        $threadKey = $this->buildThreadKey(
            $messageId,
            $payload['in_reply_to'] ?? null,
            $payload['references'] ?? [],
            $payload['subject'] ?? null,
            ['email' => $mailbox->email_address],
            $toList
        );

        $message = MailMessage::updateOrCreate(
            [
                'folder_id'  => $sentFolder->id,
                'message_id' => $messageId,
            ],
            [
                'mailbox_id' => $mailbox->id,
                'imap_uid'   => random_int(1, 2147483647),
                'subject'    => $payload['subject'] ?? null,
                'from_name'  => $mailbox->user?->name,
                'from_email' => $mailbox->email_address,
                'to'         => $toList ?: null,
                'cc'         => $ccList ?: null,
                'date'       => now(),
                'snippet'    => $payload['body_text'] ? Str::of($payload['body_text'])->limit(200) : null,
                'body_text'  => $payload['body_text'] ?? null,
                'body_html'  => $payload['body_html'] ?? null,
                'is_read'    => true,
                'thread_key' => $threadKey,
                'in_reply_to' => $payload['in_reply_to'] ?? null,
                'references'  => $payload['references'] ?? null,
                'is_archived' => false,
                'is_starred'  => false,
                'is_deleted'  => false,
            ]
        );

        foreach ($uploadedAttachments as $attachment) {
            MailAttachment::create([
                'mail_message_id' => $message->id,
                'filename'        => $attachment['filename'],
                'mime'            => $attachment['mime'],
                'size'            => $attachment['size'],
                'storage_path'    => $attachment['storage_path'],
                'content_id'      => $attachment['content_id'] ?? null,
                'is_inline'       => $attachment['is_inline'] ?? false,
            ]);
        }

        return $message;
    }

    protected function baseContext(Mailbox $mailbox, array $payload, array $uploadedAttachments, string $traceId): array
    {
        $attachmentsCount = count($uploadedAttachments);
        $attachmentsTotalSize = collect($uploadedAttachments)->sum(fn ($item) => (int) ($item['size'] ?? 0));

        $allRecipients = array_merge($payload['to'] ?? [], $payload['cc'] ?? [], $payload['bcc'] ?? []);

        return [
            'trace_id'             => $traceId,
            'mailbox_id'           => $mailbox->id,
            'user_id'              => $mailbox->user_id ?? null,
            'from_email'           => $mailbox->email_address,
            'smtp_host'            => $mailbox->smtp_host,
            'smtp_port'            => $mailbox->smtp_port,
            'smtp_encryption'      => $mailbox->smtp_encryption,
            'to_count'             => count($payload['to'] ?? []),
            'cc_count'             => count($payload['cc'] ?? []),
            'bcc_count'            => count($payload['bcc'] ?? []),
            'subject_length'       => mb_strlen($payload['subject'] ?? ''),
            'body_length'          => mb_strlen($payload['body_text'] ?? ''),
            'attachment_count'     => $attachmentsCount,
            'attachment_total_size'=> $attachmentsTotalSize,
            'to_preview'           => $this->maskAddresses($payload['to'] ?? []),
            'cc_preview'           => $this->maskAddresses($payload['cc'] ?? []),
            'recipient_domains'    => $this->recipientDomains($allRecipients),
        ];
    }

    protected function exceptionContext(\Throwable $e): array
    {
        $context = [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ];

        if ($e->getPrevious()) {
            $context['previous'] = [
                'class'   => get_class($e->getPrevious()),
                'message' => $e->getPrevious()->getMessage(),
                'code'    => $e->getPrevious()->getCode(),
                'file'    => $e->getPrevious()->getFile(),
                'line'    => $e->getPrevious()->getLine(),
            ];
        }

        if ($e instanceof TransportExceptionInterface) {
            $context['transport'] = $this->transportContext($e);
        }

        return $context;
    }

    protected function transportContext(TransportExceptionInterface $e): array
    {
        $ctx = [];

        if (method_exists($e, 'getDebug')) {
            $ctx['debug'] = Str::limit((string) $e->getDebug(), 500);
        }

        if (method_exists($e, 'getResponse')) {
            try {
                $response = $e->getResponse();
                if ($response && method_exists($response, 'getStatusCode')) {
                    $ctx['response_status'] = $response->getStatusCode();
                }
                if ($response && method_exists($response, 'getInfo')) {
                    $ctx['response_info'] = $this->safeResponseInfo($response->getInfo());
                }
            } catch (\Throwable $responseError) {
                $ctx['response_context_error'] = $responseError->getMessage();
            }
        }

        return $ctx;
    }

    protected function safeResponseInfo($info): array
    {
        if (!is_array($info)) {
            return [];
        }

        $allowedKeys = ['debug', 'http_code', 'url', 'error', 'response_headers'];
        $filtered = [];
        foreach ($allowedKeys as $key) {
            if (isset($info[$key])) {
                $value = $info[$key];
                if (is_string($value)) {
                    $filtered[$key] = Str::limit($value, 500);
                } elseif (is_array($value)) {
                    $filtered[$key] = collect($value)->map(fn ($v) => is_string($v) ? Str::limit($v, 300) : $v)->all();
                } else {
                    $filtered[$key] = $value;
                }
            }
        }

        return $filtered;
    }

    protected function maskAddresses(array $list, int $limit = 2): array
    {
        return collect($list)
            ->take($limit)
            ->map(function ($item) {
                $email = is_array($item) ? ($item['email'] ?? '') : $item;
                $email = trim((string) $email);
                if ($email === '' || !str_contains($email, '@')) {
                    return null;
                }

                [$local, $domain] = explode('@', $email, 2);
                $local = mb_substr($local, 0, 2).(mb_strlen($local) > 2 ? '***' : '*');

                return $local.'@'.$domain;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function recipientDomains(array $list): array
    {
        return collect($list)
            ->map(function ($item) {
                $email = is_array($item) ? ($item['email'] ?? '') : $item;
                $email = trim((string) $email);
                if ($email === '' || !str_contains($email, '@')) {
                    return null;
                }
                [, $domain] = explode('@', $email, 2);
                return $domain;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function shouldAttachAsData(): bool
    {
        if (!app()->runningInConsole()) {
            return false;
        }

        $argv = implode(' ', $_SERVER['argv'] ?? []);

        return str_contains($argv, 'queue:work') || str_contains($argv, 'queue:listen');
    }

    protected function buildThreadKey(?string $messageId, ?string $inReplyTo, array $references, ?string $subject, ?array $from, array $to): string
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
            ->merge(collect($to)->map(fn ($a) => is_array($a) ? ($a['email'] ?? '') : $a)->all())
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
