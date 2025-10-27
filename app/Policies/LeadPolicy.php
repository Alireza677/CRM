<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    protected string $prefix = 'leads';

    public function viewAny(User $user): bool
    {
        return $user->can($this->prefix . '.view.company')
            || $user->can($this->prefix . '.view.department')
            || $user->can($this->prefix . '.view.team')
            || $user->can($this->prefix . '.view.own');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->checkAction($user, $lead, 'view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->prefix . '.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->checkAction($user, $lead, 'update');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->checkAction($user, $lead, 'delete');
    }

    protected function checkAction(User $user, $model, string $action): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // company
        if ($user->can("{$this->prefix}.{$action}.company")) {
            return true;
        }
        // department
        if ($user->can("{$this->prefix}.{$action}.department")) {
            $dept = $user->department ?? null;
            if ($dept && ($model->department ?? null) === $dept) {
                return true;
            }
        }
        // team
        if ($user->can("{$this->prefix}.{$action}.team")) {
            $teamIds = [];
            if (method_exists($user, 'teams')) {
                try { $teamIds = collect($user->teams)->pluck('id')->all(); } catch (\Throwable $e) { $teamIds = []; }
            }
            if (empty($teamIds) && isset($user->team_id)) {
                $teamIds = array_filter([(int) $user->team_id]);
            }
            if (!empty($teamIds) && in_array((int)($model->team_id ?? 0), $teamIds, true)) {
                return true;
            }
            // fallback to department equality when no team context
            $dept = $user->department ?? null;
            if (empty($teamIds) && $dept && ($model->department ?? null) === $dept) {
                return true;
            }
        }
        // own
        if ($user->can("{$this->prefix}.{$action}.own")) {
            return (int)($model->owner_user_id ?? 0) === (int)$user->id;
        }

        return false;
    }
}

