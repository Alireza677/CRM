<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->unsignedTinyInteger('step')->nullable()->after('status');

            // ایندکس‌های کاربردی
            $table->index(['approvable_type','approvable_id','status'], 'approvals_app_idx');
            $table->index(['user_id','status'], 'approvals_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropIndex('approvals_app_idx');
            $table->dropIndex('approvals_user_status_idx');
            $table->dropColumn('step');
        });
    }
};

