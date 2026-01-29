<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('activity_reminders')) {
            return;
        }

        try {
            DB::statement("ALTER TABLE activity_reminders MODIFY kind ENUM('relative','same_day','absolute') NOT NULL");
        } catch (\Throwable $e) {
            // Ignore if not supported (e.g., sqlite in tests)
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('activity_reminders')) {
            return;
        }

        try {
            DB::statement("ALTER TABLE activity_reminders MODIFY kind ENUM('relative','same_day') NOT NULL");
        } catch (\Throwable $e) {
            // Ignore if not supported
        }
    }
};