<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notification_rules')) {
            return;
        }

        Schema::table('notification_rules', function (Blueprint $table) {
            try {
                $table->dropUnique('notification_rules_module_event_unique');
            } catch (\Throwable $e) {
                // Index already removed; nothing to do.
            }
        });
    }

    public function down(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->unique(['module', 'event'], 'notification_rules_module_event_unique');
        });
    }
};
