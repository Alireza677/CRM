<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('activity_reminders')) {
            return;
        }

        Schema::table('activity_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_reminders', 'remind_at')) {
                $table->dateTime('remind_at')->nullable()->after('time_of_day');
                $table->index('remind_at', 'idx_activity_reminders_remind_at');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('activity_reminders')) {
            return;
        }

        Schema::table('activity_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('activity_reminders', 'remind_at')) {
                $table->dropIndex('idx_activity_reminders_remind_at');
                $table->dropColumn('remind_at');
            }
        });
    }
};