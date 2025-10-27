<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserTransferHelper
{
    /**
     * Transfer or nullify user references across tables.
     * - Uses Schema::hasColumn to guard against missing columns
     * - Does NOT touch Spatie activity logs (no activities.user_id)
     * - If $toUserId is null, sets selected FK columns (e.g., assigned_to, owner_user_id) to null
     */
    public static function transferAllData(int $fromUserId, ?int $toUserId): void
    {
        // Helper to safely update a single table.column
        $safeUpdate = static function (string $table, string $column) use ($fromUserId, $toUserId) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return;
            }
            DB::table($table)
                ->where($column, $fromUserId)
                ->update([$column => $toUserId]);
        };

        // Columns that should be updated even when $toUserId is null (set to null)
        $nullables = [
            ['opportunities', 'assigned_to'],
            ['opportunities', 'owner_user_id'],
            ['proformas',     'assigned_to'],
            ['proformas',     'owner_user_id'],
            ['documents',     'assigned_to'],
            ['documents',     'owner_user_id'],
            ['calls',         'user_id'],
        ];
        foreach ($nullables as [$table, $column]) {
            $safeUpdate($table, $column);
        }

        // Columns updated only when a replacement user exists
        if ($toUserId !== null) {
            $onlyWithReplacement = [
                ['approvals',   'user_id'],
                ['approvals',   'approved_by'],
                ['quotations',  'assigned_to'],
                ['quotations',  'product_manager'],
                ['documents',   'user_id'],
            ];
            foreach ($onlyWithReplacement as [$table, $column]) {
                $safeUpdate($table, $column);
            }

            // If logs must be transferred, uncomment below to reassign Spatie activitylog causer
            // $activityLogTable = config('activitylog.table_name', 'activity_log');
            // if (Schema::hasTable($activityLogTable) && Schema::hasColumn($activityLogTable, 'causer_id') && Schema::hasColumn($activityLogTable, 'causer_type')) {
            //     DB::table($activityLogTable)
            //         ->where('causer_type', \App\Models\User::class)
            //         ->where('causer_id', $fromUserId)
            //         ->update(['causer_id' => $toUserId]);
            // }
        }
    }
}
