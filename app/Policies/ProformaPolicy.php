<?php

namespace App\Policies;

use App\Models\Proforma;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProformaPolicy
{
    /**
     * اجازهٔ دیدن لیست کلی (می‌تونی به دلخواه محدودتر کنی)
     */
    public function viewAny(User $user): bool
    {
        return true; // هر کاربر لاگین‌شده
    }

    /**
     * اجازهٔ مشاهدهٔ یک رکورد
     */
    public function view(User $user, Proforma $proforma): bool
    {
        return $this->isAdmin($user) || (int)$proforma->assigned_to === (int)$user->id;
    }

    /**
     * اجازهٔ ایجاد رکورد
     */
    public function create(User $user): bool
    {
        return true; // هر کاربر مجاز به ایجاد (در صورت نیاز محدود کن)
    }

    /**
     * اجازهٔ ویرایش
     * - در وضعیت "ارسال برای تاییدیه" ویرایش ممنوع است.
     * - در غیر این‌صورت: ادمین یا شخصِ assign شده.
     */
    public function update(User $user, Proforma $proforma): bool
    {
        $stage = $proforma->approval_stage ?? $proforma->proforma_stage ?? null;

        if ($stage === 'sent_for_approval') {
            return false;
        }

        return $this->isAdmin($user) || (int)$proforma->assigned_to === (int)$user->id;
    }

    /**
     * اجازهٔ حذف
     * - در وضعیت "ارسال برای تاییدیه" حذف ممنوع است.
     * - در غیر این‌صورت: فقط ادمین (مطابق خواستهٔ قبلی‌ات)
     */
    public function delete(User $user, Proforma $proforma): bool
    {
        $stage = $proforma->approval_stage ?? $proforma->proforma_stage ?? null;

        if ($stage === 'sent_for_approval') {
            return false;
        }

        return $this->isAdmin($user);
    }

    /**
     * بازگردانی از سطل زباله (در صورت استفاده از SoftDeletes)
     */
    public function restore(User $user, Proforma $proforma): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * حذف دائمی
     */
    public function forceDelete(User $user, Proforma $proforma): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * اجازهٔ تایید پیش‌فاکتور
     * - فقط وقتی مجاز است که رکورد در وضعیت "ارسال برای تاییدیه" باشد.
     * - مجاز هستند: approver_1 یا approver_2 یا emergency_approver_id.
     */
    public function approve(User $user, \App\Models\Proforma $proforma): bool
{
    $userId = (int) $user->id;

    // 1) فقط در مراحل قابل تایید اجازه بده
    $stage = $proforma->approval_stage ?? $proforma->proforma_stage ?? null;
    if (! in_array($stage, ['send_for_approval', 'awaiting_approval', 'awaiting_second_approval'], true)) {
        return false;
    }

    // 2) رکورد pending مرحله‌ی جاری
    $pending = $proforma->approvals()
        ->where('status', 'pending')
        ->orderBy('step')
        ->orderBy('id')
        ->first();

    if (! $pending) {
        return false;
    }

    // 3) خودِ کاربر approver همین مرحله است
    if ((int) $pending->user_id === $userId) {
        return true;
    }

    // 4) جایگزین اضطراری تعریف‌شده در قانون اتوماسیون
    $rule = $proforma->automationRule()->first();
    if ($rule && (int) $rule->emergency_approver_id === $userId) {
        return true;
    }

    // 5) در غیر این صورت مجاز نیست
    return false;
}


    /**
     * تشخیص ادمین
     * - اگر متد isAdmin() در مدل User داشته باشی، از همون استفاده می‌کنه.
     * - در غیر این‌صورت تلاش می‌کنه نقش را از فیلد/رابطه role تشخیص بده.
     */
    private function isAdmin(User $user): bool
    {
        if (method_exists($user, 'isAdmin')) {
            return (bool) $user->isAdmin();
        }

        // Fallback های رایج
        // 1) اگر رابطه role روی User داری و name در آن ذخیره می‌شود
        if (property_exists($user, 'role') && is_object($user->role) && property_exists($user->role, 'name')) {
            return strtolower((string)$user->role->name) === 'admin';
        }

        // 2) اگر مستقیماً فیلدی مثل role_name روی User داری
        if (isset($user->role_name)) {
            return strtolower((string)$user->role_name) === 'admin';
        }

        return false;
    }
}
