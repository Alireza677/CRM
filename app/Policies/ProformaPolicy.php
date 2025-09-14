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

    public function update(User $user, Proforma $proforma): bool
    {
        // ❗ فقط در پیش‌نویس اجازه بده
        if (! $proforma->canEdit()) {
            return false;
        }
        // ادمین یا شخص assign‌شده
        return $this->isAdmin($user) || (int)$proforma->assigned_to === (int)$user->id;
    }

    /**
     * اجازهٔ حذف
     * - در وضعیت "ارسال برای تاییدیه" حذف ممنوع است.
     * - در غیر این‌صورت: فقط ادمین (مطابق خواستهٔ قبلی‌ات)
     */
    public function delete(User $user, Proforma $proforma): bool
    {
        // ادمین همیشه می‌تواند حذف کند (draft یا هر مرحله‌ی دیگر)
        if ($this->isAdmin($user)) {
            return true;
        }

        // غیرادمین‌ها اجازه حذف ندارند
        return false;

        // اگر خواستی غیرادمین فقط در draft حذف کند، این را جایگزین خط بالا کن:
        // return $proforma->canEdit() && (int)$proforma->assigned_to === (int)$user->id;
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
    public function approve(User $user, Proforma $proforma): bool
    {
        $userId = (int) $user->id;

        // ✅ فقط وقتی در مراحل قابل تأیید هست
        $stage = strtolower((string)($proforma->approval_stage ?? $proforma->proforma_stage ?? ''));
        if (! in_array($stage, ['send_for_approval','awaiting_second_approval'], true)) {
            return false;
        }

        // ✅ همین مرحله pending داشته باشد
        $pending = $proforma->approvals()
            ->where('status','pending')
            ->orderBy('step')->orderBy('id')
            ->first();

        if (! $pending) {
            return false;
        }

        // تاییدکننده اصلی مرحله
        if ((int)$pending->user_id === $userId) {
            return true;
        }

        // یا emergency approver
        $rule = $proforma->automationRule()->first();
        return $rule && (int)$rule->emergency_approver_id === $userId;
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

        // Spatie (اگر داری)
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin','super-admin'])) {
            return true;
        }
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('delete proformas')) {
            return true;
        }

        // role_name تکی
        if (!empty($user->role_name) && in_array(strtolower($user->role_name), ['admin','super-admin'], true)) {
            return true;
        }

        // رابطه role
        try {
            $role = $user->role ?? null;
            $name = is_object($role) ? ($role->name ?? null) : (is_string($role) ? $role : null);
            if (!empty($name) && in_array(strtolower($name), ['admin','super-admin'], true)) {
                return true;
            }
        } catch (\Throwable $e) {}

        // رابطه roles چندتایی
        try {
            if (isset($user->roles)) {
                foreach ($user->roles as $r) {
                    $n = is_object($r) ? ($r->name ?? null) : (is_string($r) ? $r : null);
                    if (!empty($n) && in_array(strtolower($n), ['admin','super-admin'], true)) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {}

        return false;
    }

}
