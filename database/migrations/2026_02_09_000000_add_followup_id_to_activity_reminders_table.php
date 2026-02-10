<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('activity_reminders')) {
            return;
        }

        Schema::table('activity_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_reminders', 'followup_id')) {
                $table->foreignId('followup_id')
                    ->nullable()
                    ->after('activity_id')
                    ->constrained('activity_followups')
                    ->cascadeOnDelete();
                $table->index('followup_id', 'idx_activity_reminders_followup_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('activity_reminders')) {
            return;
        }

        Schema::table('activity_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('activity_reminders', 'followup_id')) {
                $table->dropForeign(['followup_id']);
                $table->dropIndex('idx_activity_reminders_followup_id');
                $table->dropColumn('followup_id');
            }
        });
    }
};
