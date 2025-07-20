<?php

namespace App\Helpers;

use App\Models\Opportunity;
use App\Models\Proforma;
use App\Models\Approval;
use App\Models\Quotation;
use App\Models\Activity;
use App\Models\Call;
use App\Models\Document;

class UserTransferHelper
{
    public static function transferAllData(int $fromUserId, int $toUserId): void
    {
        // فرصت‌های فروش
        Opportunity::where('assigned_to', $fromUserId)->update(['assigned_to' => $toUserId]);

        // پیش‌فاکتورها
        Proforma::where('assigned_to', $fromUserId)->update(['assigned_to' => $toUserId]);

        // تاییدیه‌ها
        Approval::where('user_id', $fromUserId)->update(['user_id' => $toUserId]);
        Approval::where('approved_by', $fromUserId)->update(['approved_by' => $toUserId]);

        // پیشنهاد قیمت‌ها
        Quotation::where('assigned_to', $fromUserId)->update(['assigned_to' => $toUserId]);
        Quotation::where('product_manager', $fromUserId)->update(['product_manager' => $toUserId]);

        // فعالیت‌ها و تماس‌ها و اسناد
        Activity::where('user_id', $fromUserId)->update(['user_id' => $toUserId]);
        Call::where('user_id', $fromUserId)->update(['user_id' => $toUserId]);
        Document::where('user_id', $fromUserId)->update(['user_id' => $toUserId]);

        // هر مدل وابسته‌ی دیگر را می‌توان اینجا اضافه کرد
    }
}
