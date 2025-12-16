<?php

namespace App\Jobs;

use App\Models\Mailbox;
use App\Services\Mail\MailSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMailboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public int $mailboxId
    ) {
    }

    public function handle(MailSyncService $service): void
    {
        $mailbox = Mailbox::query()->find($this->mailboxId);
        if (!$mailbox || !$mailbox->is_active) {
            return;
        }

        try {
            $service->syncInbox($mailbox);
        } catch (\Throwable $e) {
            Log::warning('[MAIL][JOB] SyncMailboxJob failed', [
                'mailbox_id' => $this->mailboxId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
