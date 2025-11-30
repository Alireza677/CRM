<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // تلاش برای حذف فاصله‌های اضافه و نرمال‌سازی
        try {
            DB::statement("UPDATE opportunities SET stage = REGEXP_REPLACE(TRIM(stage), '\\\\s+', ' ')");
        } catch (\Throwable $e) {
            // اگر نسخه MySQL قدیمی است و REGEXP_REPLACE ندارد، حداقل trim را اعمال می‌کنیم
            DB::statement("UPDATE opportunities SET stage = TRIM(stage)");
        }
    }

    public function down(): void
    {
        // داده بازگردانی نمی‌شود؛ این مهاجرت بدون rollback معنایی است
    }
};
