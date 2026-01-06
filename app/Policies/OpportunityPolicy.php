<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    protected string $prefix = 'opportunities';

    public function viewAny(User $user): bool
    {
        return $user->can($this->prefix . '.view.company')
            || $user->can($this->prefix . '.view.department')
            || $user->can($this->prefix . '.view.team')
            || $user->can($this->prefix . '.view.own');
    }

    public function view(User $user, Opportunity $model): bool
    {
        return $this->checkAction($user, $model, 'view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->prefix . '.create');
    }

    public function update(User $user, Opportunity $model): bool
    {
        return $this->checkAction($user, $model, 'update');
    }

    public function delete(User $user, Opportunity $model): bool
    {
        return $this->checkAction($user, $model, 'delete');
    }

    protected function checkAction(User $user, $model, string $action): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if ($user->can("{$this->prefix}.{$action}.company")) { return true; }

        if ($user->can("{$this->prefix}.{$action}.department")) {
            $dept = $user->department ?? null;
            if ($dept && ($model->department ?? null) === $dept) { return true; }
        }

        if ($user->can("{$this->prefix}.{$action}.team")) {
            $teamIds = [];
            if (method_exists($user, 'teams')) { try { $teamIds = collect($user->teams)->pluck('id')->all(); } catch (\Throwable) {} }
            if (empty($teamIds) && isset($user->team_id)) { $teamIds = array_filter([(int)$user->team_id]); }
            if (!empty($teamIds) && in_array((int)($model->team_id ?? 0), $teamIds, true)) { return true; }
            $dept = $user->department ?? null;
            if (empty($teamIds) && $dept && ($model->department ?? null) === $dept) { return true; }
        }

        if ($user->can("{$this->prefix}.{$action}.own")) {
            $uid = (int) $user->id;
            if ((int)($model->owner_user_id ?? 0) === $uid
                || (int)($model->assigned_to ?? 0) === $uid) {
                return true;
            }

            return $model->roleAssignments()
                ->where('user_id', $uid)
                ->whereIn('role_type', ['acquirer', 'relationship_owner', 'closer', 'execution_owner'])
                ->exists();
        }

        return false;
    }
}

