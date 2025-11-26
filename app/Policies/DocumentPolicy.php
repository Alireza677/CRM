<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    protected string $prefix = 'documents';

    public function viewAny(User $user): bool
    {
        return $user->can($this->prefix . '.view.company')
            || $user->can($this->prefix . '.view.department')
            || $user->can($this->prefix . '.view.team')
            || $user->can($this->prefix . '.view.own');
    }

    public function view(User $user, Document $model): bool
    {
        if (!$this->passesCategoryGate($user, $model, 'view')) {
            return false;
        }

        return $this->checkAction($user, $model, 'view');
    }

    public function download(User $user, Document $model): bool
    {
        if (!$this->passesCategoryGate($user, $model, 'download')) {
            return false;
        }

        return $this->checkAction($user, $model, 'view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->prefix . '.create');
    }

    public function update(User $user, Document $model): bool
    {
        return $this->checkAction($user, $model, 'update');
    }

    public function delete(User $user, Document $model): bool
    {
        return $this->checkAction($user, $model, 'delete');
    }

    protected function checkAction(User $user, $model, string $action): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) { return true; }
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
            return (int)($model->owner_user_id ?? 0) === (int)$user->id;
        }
        return false;
    }

    protected function passesCategoryGate(User $user, Document $model, string $action): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        $requirements = [];
        if ($model->purchase_order_id) {
            $requirements[] = 'purchase_documents.' . $action;
        }
        if ($model->opportunity_id) {
            $requirements[] = 'opportunity_documents.' . $action;
        }

        foreach ($requirements as $permission) {
            if (!$user->can($permission)) {
                return false;
            }
        }

        return true;
    }
}
