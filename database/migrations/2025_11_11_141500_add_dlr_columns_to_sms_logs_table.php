<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_logs', 'status')) {
                $table->string('status', 32)->nullable()->after('status_text')->index();
            }
            if (!Schema::hasColumn('sms_logs', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('sms_logs', 'error_code')) {
                $table->string('error_code', 64)->nullable()->after('status_updated_at');
            }
            if (!Schema::hasColumn('sms_logs', 'error_message')) {
                $table->string('error_message', 255)->nullable()->after('error_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sms_logs', 'status')) $table->dropColumn('status');
            if (Schema::hasColumn('sms_logs', 'status_updated_at')) $table->dropColumn('status_updated_at');
            if (Schema::hasColumn('sms_logs', 'error_code')) $table->dropColumn('error_code');
            if (Schema::hasColumn('sms_logs', 'error_message')) $table->dropColumn('error_message');
        });
    }
};

