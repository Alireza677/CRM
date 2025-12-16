<?php

namespace App\Console\Commands;

use App\Models\Mailbox;
use App\Services\Mail\MailSyncService;
use Illuminate\Console\Command;

class MailSyncCommand extends Command
{
    protected $signature = 'mail:sync {--user_id=}';

    protected $description = 'همگام‌سازی ایمیل‌های ورودی کاربر از IMAP';

    public function __construct(
        protected MailSyncService $mailSyncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $userId = $this->option('user_id');

        $query = Mailbox::query()->where('is_active', true);
        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        $mailboxes = $query->get();
        if ($mailboxes->isEmpty()) {
            $this->warn('هیچ صندوق ایمیل فعالی یافت نشد.');
            return self::SUCCESS;
        }

        foreach ($mailboxes as $mailbox) {
            $this->line("⏳ Sync mailbox #{$mailbox->id} ({$mailbox->email_address})");
            try {
                $imported = $this->mailSyncService->syncInbox($mailbox);
                $this->info("✔ synced ({$imported} new)");
            } catch (\Throwable $e) {
                $this->error("خطا در همگام‌سازی mailbox #{$mailbox->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
