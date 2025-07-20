<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\User;

class FixOrphanAssignments extends Command
{
    protected $signature = 'leads:fix-orphan-assignments 
                            {--to= : ID کاربر مقصد برای انتقال ارجاع‌ها}
                            {--dry : فقط گزارش بگیر، تغییری اعمال نکن}';

    protected $description = 'انتقال ارجاع‌های سرنخ‌هایی که به کاربر حذف‌شده اشاره می‌کنند';

    public function handle()
    {
        $toUserId = $this->option('to');
        $isDryRun = $this->option('dry');

        if (!$toUserId) {
            $this->error('لطفاً با گزینه --to=ID کاربر مقصد را مشخص کنید.');
            return 1;
        }

        $newUser = User::find($toUserId);
        if (!$newUser) {
            $this->error("کاربر با آیدی {$toUserId} یافت نشد.");
            return 1;
        }

        // پیدا کردن سرنخ‌هایی که ارجاع دارند ولی کاربرشان حذف شده
        $leads = Lead::whereNotNull('assigned_to')
            ->whereDoesntHave('assignedTo')
            ->get();

        if ($leads->isEmpty()) {
            $this->info('همه ارجاع‌ها معتبر هستند. موردی برای اصلاح وجود ندارد.');
            return 0;
        }

        $this->info("تعداد {$leads->count()} سرنخ با ارجاع نامعتبر یافت شد.");

        foreach ($leads as $lead) {
            $this->line("سرنخ #{$lead->id} (ارجاع فعلی: user_id={$lead->assigned_to})");

            if (!$isDryRun) {
                $lead->assigned_to = $toUserId;
                $lead->save();
            }
        }

        if ($isDryRun) {
            $this->warn('حالت dry-run فعال بود. هیچ تغییری ذخیره نشد.');
        } else {
            $this->info("همه ارجاع‌ها با موفقیت به کاربر [{$newUser->name}] منتقل شدند.");
        }

        return 0;
    }
}
