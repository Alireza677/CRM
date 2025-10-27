<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Make common user reference columns nullable if present (MySQL)
        $changes = [
            ['opportunities', 'assigned_to'],
            ['opportunities', 'owner_user_id'],
            ['proformas',     'assigned_to'],
            ['proformas',     'owner_user_id'],
            ['documents',     'assigned_to'],
            ['documents',     'owner_user_id'],
            ['quotations',    'assigned_to'],
        ];

        foreach ($changes as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                try {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NULL");
                } catch (\Throwable $e) {
                    // Ignore if driver doesn't support this or already nullable
                }
            }
        }
    }

    public function down(): void
    {
        // No-op: keeping columns nullable is safe and intentional
    }
};

