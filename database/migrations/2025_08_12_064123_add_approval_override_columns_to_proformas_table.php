<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('proformas', function (Blueprint $table) {
            // اگر مرحله ۱ را می‌خواهی رسمی ثبت کنی:
            if (!Schema::hasColumn('proformas', 'first_approved_by')) {
                $table->foreignId('first_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('updated_at');
            }
            if (!Schema::hasColumn('proformas', 'first_approved_at')) {
                $table->timestamp('first_approved_at')->nullable()->after('first_approved_by');
            }

            // حالت/مسیر تأیید: standard (دو مرحله) | override (ادمین جایگزین)
            if (!Schema::hasColumn('proformas', 'approval_mode')) {
                $table->enum('approval_mode', ['standard', 'override'])->nullable()->after('first_approved_at');
            }

            // چه کسی تأیید نهایی کرده
            if (!Schema::hasColumn('proformas', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approval_mode');
            }
        });
    }

    public function down(): void {
        Schema::table('proformas', function (Blueprint $table) {
            if (Schema::hasColumn('proformas', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('proformas', 'approval_mode')) {
                $table->dropColumn('approval_mode');
            }
            if (Schema::hasColumn('proformas', 'first_approved_at')) {
                $table->dropColumn('first_approved_at');
            }
            if (Schema::hasColumn('proformas', 'first_approved_by')) {
                $table->dropConstrainedForeignId('first_approved_by');
            }
        });
    }
};
