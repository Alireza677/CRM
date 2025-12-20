<?php

namespace App\Http\Controllers;

use App\Models\MailMessage;
use App\Models\MailAttachment;
use App\Models\Mailbox;
use App\Services\Mail\MailSendService;
use App\Services\Mail\MailSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MailController extends Controller
{
    public function index(Request $request)
    {
        $mailbox = $request->user()?->mailbox;
        $folderKey = strtolower($request->string('folder')->toString() ?: 'inbox');
        $search = $request->string('q')->toString();

        $inboxFolder = $mailbox?->folders()->firstOrCreate(['imap_path' => 'INBOX'], ['name' => 'INBOX']);
        $sentFolder  = $mailbox?->folders()->firstOrCreate(['imap_path' => 'Sent'], ['name' => 'Sent']);

        $folderCounts = [
            'inbox'   => 0,
            'sent'    => 0,
            'archive' => 0,
            'trash'   => 0,
        ];

        $threads = collect();
        if ($mailbox) {
            $base = MailMessage::query()
                ->where('mailbox_id', $mailbox->id)
                ->whereNotNull('thread_key');

            if ($folderKey === 'sent') {
                $base->where('folder_id', $sentFolder?->id);
            } elseif ($folderKey === 'archive') {
                $base->where('is_archived', true)->where('is_deleted', false);
            } elseif ($folderKey === 'trash') {
                $base->where('is_deleted', true);
            } else {
                $base->where('folder_id', $inboxFolder?->id)
                    ->where('is_archived', false)
                    ->where('is_deleted', false);
            }

            if (!empty($search)) {
                $base->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('from_email', 'like', "%{$search}%")
                        ->orWhere('from_name', 'like', "%{$search}%")
                        ->orWhere('snippet', 'like', "%{$search}%")
                        ->orWhere('body_text', 'like', "%{$search}%");
                });
            }

            $folderCounts['inbox'] = MailMessage::query()->where('mailbox_id', $mailbox->id)
                ->where('folder_id', $inboxFolder?->id)->where('is_archived', false)->where('is_deleted', false)->count();
            $folderCounts['sent'] = MailMessage::query()->where('mailbox_id', $mailbox->id)
                ->where('folder_id', $sentFolder?->id)->where('is_deleted', false)->count();
            $folderCounts['archive'] = MailMessage::query()->where('mailbox_id', $mailbox->id)
                ->where('is_archived', true)->where('is_deleted', false)->count();
            $folderCounts['trash'] = MailMessage::query()->where('mailbox_id', $mailbox->id)
                ->where('is_deleted', true)->count();

            $threadsQuery = $base->cloneWithout(['orders', 'columns'])
                ->selectRaw('thread_key, MAX(id) as last_id, MAX(date) as last_date, COUNT(*) as messages_count, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
                ->groupBy('thread_key')
                ->orderByDesc('last_date');

            $threads = $threadsQuery->paginate(15);

            $lastIds = $threads->pluck('last_id')->filter()->all();
            $lastMessages = MailMessage::query()
                ->whereIn('id', $lastIds)
                ->get()
                ->keyBy('id');

            $threads->getCollection()->transform(function ($row) use ($lastMessages) {
                $row->last_message = $lastMessages[$row->last_id] ?? null;
                return $row;
            });
        }

        return view('mail.index', [
            'mailbox'         => $mailbox,
            'inboxFolder'     => $inboxFolder,
            'sentFolder'      => $sentFolder,
            'threads'         => $threads,
            'selectedFolder'  => $folderKey,
            'search'          => $search,
            'folderCounts'    => $folderCounts,
        ]);
    }

    public function show(Request $request, MailMessage $message, MailSyncService $mailSyncService)
    {
        $mailbox = $request->user()?->mailbox;
        abort_unless($mailbox && $mailbox->id === $message->mailbox_id, 403);

        return redirect()->route('mail.thread', ['thread_key' => $message->thread_key]);
    }

    public function thread(Request $request, string $threadKey, MailSyncService $mailSyncService)
    {
        $mailbox = $request->user()?->mailbox;
        abort_unless($mailbox, 403);

        $messages = MailMessage::query()
            ->where('mailbox_id', $mailbox->id)
            ->where('thread_key', $threadKey)
            ->orderBy('date')
            ->orderBy('id')
            ->with('attachments')
            ->get();

        abort_if($messages->isEmpty(), 404);

        $messages->each(function (MailMessage $msg) use ($mailSyncService) {
            if (empty($msg->body_text) && empty($msg->body_html)) {
                $mailSyncService->hydrateMessageBody($msg);
                $msg->refresh();
            }
        });

        MailMessage::query()
            ->where('mailbox_id', $mailbox->id)
            ->where('thread_key', $threadKey)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('mail.thread', [
            'mailbox'  => $mailbox,
            'threadKey'=> $threadKey,
            'messages' => $messages,
        ]);
    }

    public function compose(Request $request)
    {
        $mailbox = $request->user()?->mailbox;

        return view('mail.compose', [
            'mailbox' => $mailbox,
            'defaults' => [
                'to'      => $request->string('to')->toString(),
                'cc'      => $request->string('cc')->toString(),
                'subject' => $request->string('subject')->toString(),
                'body'    => $request->string('body')->toString(),
            ],
        ]);
    }

    public function send(Request $request, MailSendService $mailer)
    {
        $mailbox = $request->user()?->mailbox;
        abort_unless($mailbox, 400, 'صندوق ایمیل تنظیم نشده است.');
        $traceId = (string) Str::uuid();

        $data = $request->validate([
            'to'               => ['required', 'string', 'max:2000'],
            'cc'               => ['nullable', 'string', 'max:2000'],
            'subject'          => ['nullable', 'string', 'max:255'],
            'body'             => ['nullable', 'string'],
            'attachments.*'    => ['file', 'max:10240'],
            'in_reply_to'      => ['nullable', 'string', 'max:255'],
            'references'       => ['nullable', 'string', 'max:2000'],
        ]);

        $to = $this->parseRecipients($data['to'] ?? '');
        $cc = $this->parseRecipients($data['cc'] ?? '');

        if (empty($to)) {
            return Redirect::back()->withInput()->withErrors(['to' => 'گیرنده معتبر وارد کنید.']);
        }

        $bodyText = trim((string) ($data['body'] ?? ''));
        $attachments = [];
        foreach ($request->file('attachments', []) as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $storedPath = $file->store('mail/'.$request->user()->id.'/attachments', 'private');
            $attachments[] = [
                'filename'      => $file->getClientOriginalName(),
                'mime'          => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'storage_path'  => $storedPath,
            ];
        }

        $references = $this->parseReferences($data['references'] ?? '');

        $payload = [
            'to'         => $to,
            'cc'         => $cc,
            'subject'    => $data['subject'] ?? '',
            'body_text'  => $bodyText,
            'body_html'  => nl2br(e($bodyText)),
            'message_id' => null,
            'in_reply_to'=> $request->string('in_reply_to')->toString(),
            'references' => $references,
            'bcc'        => [],
        ];

        $logContext = $this->mailLogContext($mailbox, $payload, $attachments, $traceId);
        Log::channel('mail')->info('mail.controller.start', $logContext + ['stage' => 'start']);

        $message = $mailer->send($mailbox, $payload, $attachments, $traceId);

        if (!$message) {
            Log::channel('mail')->warning('mail.controller.failed', $logContext + ['stage' => 'after_send']);
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'ارسال ناموفق بود.',
                    'trace_id' => $traceId,
                ], 422);
            }

            return Redirect::back()
                ->withInput()
                ->with('mail_trace_id', $traceId)
                ->withErrors(['general' => 'ارسال ناموفق بود. کد پیگیری: '.$traceId]);
        }

        Log::channel('mail')->info('mail.controller.success', $logContext + [
            'stage' => 'after_send',
            'stored_message_id' => $message->id,
        ]);

        if ($request->expectsJson()) {
            $html = view('mail.partials.message_bubble', [
                'message' => $message->fresh(['attachments']),
                'mailbox' => $mailbox,
            ])->render();

            return response()->json([
                'ok' => true,
                'message_id' => $message->id,
                'thread_key' => $message->thread_key,
                'html' => $html,
            ]);
        }

        return redirect()->route('mail.show', $message)->with('success', 'ایمیل ارسال شد.');
    }

    public function bulk(Request $request)
    {
        $mailbox = $request->user()?->mailbox;
        abort_unless($mailbox, 403);

        $data = $request->validate([
            'action'    => ['required', 'in:mark_read,mark_unread,star,unstar,archive,unarchive,delete'],
            'threads'   => ['required', 'array'],
            'threads.*' => ['string'],
        ]);

        $query = MailMessage::query()
            ->where('mailbox_id', $mailbox->id)
            ->whereIn('thread_key', $data['threads']);

        switch ($data['action']) {
            case 'mark_read':
                $query->update(['is_read' => true]);
                break;
            case 'mark_unread':
                $query->update(['is_read' => false]);
                break;
            case 'star':
                $query->update(['is_starred' => true]);
                break;
            case 'unstar':
                $query->update(['is_starred' => false]);
                break;
            case 'archive':
                $query->update(['is_archived' => true, 'is_deleted' => false]);
                break;
            case 'unarchive':
                $query->update(['is_archived' => false]);
                break;
            case 'delete':
                $query->update(['is_deleted' => true, 'is_archived' => false]);
                break;
        }

        return back()->with('success', 'اقدام دسته‌ای انجام شد.');
    }

    public function downloadAttachment(Request $request, MailAttachment $attachment)
    {
        $mailbox = $request->user()?->mailbox;
        abort_unless($mailbox && $attachment->message && $attachment->message->mailbox_id === $mailbox->id, 403);

        return Storage::disk('private')->download($attachment->storage_path, $attachment->filename);
    }

    protected function parseRecipients(?string $value): array
    {
        $parts = collect(preg_split('/[,;]/', (string) $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->map(function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
            })
            ->filter()
            ->values()
            ->all();

        return $parts;
    }

    protected function parseReferences(?string $value): array
    {
        if (empty($value)) {
            return [];
        }
        return collect(preg_split('/[\\s,]+/', (string) $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function mailLogContext(Mailbox $mailbox, array $payload, array $attachments, string $traceId): array
    {
        $attachmentsTotal = collect($attachments)->sum(fn ($item) => (int) ($item['size'] ?? 0));
        $allRecipients = array_merge($payload['to'] ?? [], $payload['cc'] ?? [], $payload['bcc'] ?? []);

        return [
            'trace_id'              => $traceId,
            'mailbox_id'            => $mailbox->id,
            'user_id'               => $mailbox->user_id ?? null,
            'from_email'            => $mailbox->email_address,
            'smtp_host'             => $mailbox->smtp_host,
            'smtp_port'             => $mailbox->smtp_port,
            'smtp_encryption'       => $mailbox->smtp_encryption,
            'to_count'              => count($payload['to'] ?? []),
            'cc_count'              => count($payload['cc'] ?? []),
            'bcc_count'             => count($payload['bcc'] ?? []),
            'subject_length'        => mb_strlen($payload['subject'] ?? ''),
            'body_length'           => mb_strlen($payload['body_text'] ?? ''),
            'attachment_count'      => count($attachments),
            'attachment_total_size' => $attachmentsTotal,
            'to_preview'            => $this->maskAddressesForLog($payload['to'] ?? []),
            'cc_preview'            => $this->maskAddressesForLog($payload['cc'] ?? []),
            'recipient_domains'     => $this->recipientDomainsForLog($allRecipients),
        ];
    }

    protected function maskAddressesForLog(array $list, int $limit = 2): array
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

    protected function recipientDomainsForLog(array $list): array
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
}
