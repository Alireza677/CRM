<?php

namespace App\Policies;

use App\Models\Proforma;
use App\Models\User;

class ProformaPolicy
{
    protected string $prefix = 'proformas';

    public function viewAny(User $user): bool
    {
        return $user->can($this->prefix . '.view.company')
            || $user->can($this->prefix . '.view.department')
            || $user->can($this->prefix . '.view.team')
            || $user->can($this->prefix . '.view.own');
    }

    public function view(User $user, Proforma $model): bool
    {
        return $this->checkAction($user, $model, 'view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->prefix . '.create');
    }

    public function update(User $user, Proforma $model): bool
    {
        return $this->checkAction($user, $model, 'update');
    }

    public function delete(User $user, Proforma $model): bool
    {
        return $this->checkAction($user, $model, 'delete');
    }

    /**
     * Determine whether the user can approve/reject the given proforma.
     * Shows approve/reject actions only to the current step approver
     * or the emergency approver defined by the automation rule, and
     * only while the proforma is in an approval-in-progress stage.
     */
    public function approve(User $user, Proforma $model): bool
    {
        $stage = strtolower((string)($model->approval_stage ?? $model->proforma_stage ?? ''));
        if (!in_array($stage, ['send_for_approval', 'awaiting_second_approval'], true)) {
            return false;
        }

        try {
            $pending = $model->approvals()
                ->where('status', 'pending')
                ->orderBy('step')
                ->orderBy('id')
                ->first();
        } catch (\Throwable) {
            $pending = null;
        }

        if (!$pending) {
            return false;
        }

        if ((int) $pending->user_id === (int) $user->id) {
            return true;
        }

        // Allow emergency approver (if configured on the related automation rule)
        try {
            $rule = $model->automationRule()->first();
        } catch (\Throwable) {
            $rule = null;
        }

        if ($rule && (int) $rule->emergency_approver_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    protected function checkAction(User $user, $model, string $action): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) { return true; }
        if ($user->can("{$this->prefix}.{$action}.company")) { return true; }
        if ($user->can("{$this->prefix}.{$action}.department")) {
            $dept = $user->department ?? null;
            if ($dept && ($model->department ?? null) === $dept) { return true; }
            if (($model->department ?? null) === null && (int)($model->assigned_to ?? 0) === (int)$user->id) {
                return true;
            }
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
            return (int)($model->owner_user_id ?? 0) === $uid
                || (int)($model->assigned_to ?? 0) === $uid
                || (int)($model->created_by ?? 0) === $uid;
        }
        return false;
    }
}
