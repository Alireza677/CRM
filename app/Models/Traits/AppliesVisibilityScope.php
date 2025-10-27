<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait AppliesVisibilityScope
{
    /**
     * Scope query by row-level visibility according to user's permissions.
     *
     * @param Builder $query
     * @param User $user
     * @param string $modulePrefix e.g. 'leads', 'opportunities'
     */
    public function scopeVisibleFor(Builder $query, User $user, string $modulePrefix): Builder
    {
        // Admin shortcut
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        // Company-level permission: no restriction
        if ($user->can($modulePrefix . '.view.company')) {
            return $query;
        }

        // Department-level
        if ($user->can($modulePrefix . '.view.department')) {
            $dept = $user->department ?? null;
            if ($dept) {
                return $query->where($query->getModel()->getTable() . '.department', $dept);
            }
            // if no department, fall back to no additional filtering beyond own/team
        }

        // Team-level
        if ($user->can($modulePrefix . '.view.team')) {
            $teamIds = [];
            // If a teams() relation exists and returns a relation/collection, use it
            if (method_exists($user, 'teams')) {
                try {
                    $teamIds = collect($user->teams)->pluck('id')->filter()->values()->all();
                } catch (\Throwable $e) {
                    $teamIds = [];
                }
            }
            // Fallback to single team_id column if present
            if (empty($teamIds) && isset($user->team_id)) {
                $teamIds = array_filter([(int) $user->team_id]);
            }

            if (!empty($teamIds)) {
                return $query->whereIn($query->getModel()->getTable() . '.team_id', $teamIds);
            }

            // If no team info available, treat as department-level where possible
            $dept = $user->department ?? null;
            if ($dept) {
                return $query->where($query->getModel()->getTable() . '.department', $dept);
            }
        }

        // Own-level
        if ($user->can($modulePrefix . '.view.own')) {
            $model = $query->getModel();
            $table = $model->getTable();

            $conditions = [];

            // Owner filter if column exists
            if (Schema::hasColumn($table, 'owner_user_id')) {
                $conditions[] = [$table . '.owner_user_id', '=', $user->id];
            }

            // Created-by fallback if column exists
            if (Schema::hasColumn($table, 'created_by')) {
                $conditions[] = [$table . '.created_by', '=', $user->id];
            }

            // Assignee filter: prefer model-declared assignee column if available, else conventional 'assigned_to'
            $assigneeColumn = property_exists($model, 'assigneeColumn') ? $model->assigneeColumn : 'assigned_to';
            if ($assigneeColumn && Schema::hasColumn($table, $assigneeColumn)) {
                $conditions[] = [$table . '.' . $assigneeColumn, '=', $user->id];
            }

            // If we collected any relevant condition, apply as OR group; else fall back to a safe false condition
            if (!empty($conditions)) {
                return $query->where(function (Builder $q) use ($conditions) {
                    foreach ($conditions as $idx => $cond) {
                        [$col, $op, $val] = $cond;
                        if ($idx === 0) {
                            $q->where($col, $op, $val);
                        } else {
                            $q->orWhere($col, $op, $val);
                        }
                    }
                });
            }

            // No known columns to filter on -> deny
            return $query->whereRaw('1 = 0');
        }

        // Default: no permission → empty result
        return $query->whereRaw('1 = 0');
    }
}
