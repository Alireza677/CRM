<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait AppliesVisibilityScope
{
    public function scopeVisibleFor(Builder $query, User $user, string $modulePrefix): Builder
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        if ($user->can($modulePrefix . '.view.any') || $user->can($modulePrefix . '.view.company')) {
            return $query;
        }

        $callbacks = [];
        $table = $query->getModel()->getTable();

        if ($user->can($modulePrefix . '.view.team')) {
            $teamIds = [];
            if (method_exists($user, 'teams')) {
                try {
                    $teamIds = collect($user->teams)->pluck('id')->filter()->values()->all();
                } catch (\Throwable $e) {
                    $teamIds = [];
                }
            }
            if (empty($teamIds) && isset($user->team_id)) {
                $teamIds = array_filter([(int) $user->team_id]);
            }
            if (!empty($teamIds)) {
                $callbacks[] = function (Builder $q, bool $first) use ($teamIds, $table) {
                    $method = $first ? 'whereIn' : 'orWhereIn';
                    $q->{$method}($table . '.team_id', $teamIds);
                };
            }
        }

        if ($user->can($modulePrefix . '.view.department')) {
            $dept = $user->department ?? null;
            if (!is_null($dept)) {
                $callbacks[] = function (Builder $q, bool $first) use ($dept, $table) {
                    $method = $first ? 'where' : 'orWhere';
                    $method === 'where'
                        ? $q->where($table . '.department', $dept)
                        : $q->orWhere($table . '.department', $dept);
                };
            }
        }

        if ($user->can($modulePrefix . '.view.own')
            || $user->can($modulePrefix . '.view.team')
            || $user->can($modulePrefix . '.view.department')) {
            $ownConditions = $this->ownVisibilityConditions($query, $user);
            if (!empty($ownConditions)) {
                $callbacks[] = function (Builder $q, bool $first) use ($ownConditions) {
                    $qMethod = $first ? 'where' : 'orWhere';
                    $q->{$qMethod}(function (Builder $inner) use ($ownConditions) {
                        foreach ($ownConditions as $idx => $cond) {
                            [$col, $val] = $cond;
                            $idx === 0
                                ? $inner->where($col, $val)
                                : $inner->orWhere($col, $val);
                        }
                    });
                };
            }
        }

        if (empty($callbacks)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($callbacks) {
            foreach ($callbacks as $idx => $callback) {
                $callback($q, $idx === 0);
            }
        });
    }

    protected function ownVisibilityConditions(Builder $query, User $user): array
    {
        $model = $query->getModel();
        $table = $model->getTable();

        $conditions = [];

        if (Schema::hasColumn($table, 'owner_user_id')) {
            $conditions[] = [$table . '.owner_user_id', $user->id];
        }

        if (Schema::hasColumn($table, 'created_by')) {
            $conditions[] = [$table . '.created_by', $user->id];
        }

        $assigneeColumn = property_exists($model, 'assigneeColumn') ? $model->assigneeColumn : 'assigned_to';
        if ($assigneeColumn && Schema::hasColumn($table, $assigneeColumn)) {
            $conditions[] = [$table . '.' . $assigneeColumn, $user->id];
        }

        return $conditions;
    }
}
