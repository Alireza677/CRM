<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    protected string $prefix = 'purchase_orders';

    public function viewAny(User $user): bool
    {
        return $this->hasAnyScope($user, 'view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkAction($user, $purchaseOrder, 'view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->prefix . '.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkAction($user, $purchaseOrder, 'update');
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkAction($user, $purchaseOrder, 'delete');
    }

    protected function checkAction(User $user, PurchaseOrder $purchaseOrder, string $action): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if ($user->can("{$this->prefix}.{$action}.company")) {
            return true;
        }

        $owner = $this->ownerUser($purchaseOrder);

        if ($owner && $user->can("{$this->prefix}.{$action}.department")) {
            $userDept = trim((string) ($user->department ?? ''));
            $ownerDept = trim((string) ($owner->department ?? ''));
            if ($userDept !== '' && strcasecmp($userDept, $ownerDept) === 0) {
                return true;
            }
        }

        if ($owner && $user->can("{$this->prefix}.{$action}.team")) {
            $userTeams = $this->userTeamIds($user);
            $ownerTeamId = (int) ($owner->team_id ?? 0);
            if ($ownerTeamId && in_array($ownerTeamId, $userTeams, true)) {
                return true;
            }

            if (empty($userTeams)) {
                $userDept = trim((string) ($user->department ?? ''));
                $ownerDept = trim((string) ($owner->department ?? ''));
                if ($userDept !== '' && strcasecmp($userDept, $ownerDept) === 0) {
                    return true;
                }
            }
        }

        if ($user->can("{$this->prefix}.{$action}.own")) {
            $ownerIds = array_filter([
                (int) ($purchaseOrder->requested_by ?? 0),
                (int) ($purchaseOrder->assigned_to ?? 0),
            ]);
            if (in_array((int) $user->id, $ownerIds, true)) {
                return true;
            }
        }

        return false;
    }

    protected function ownerUser(PurchaseOrder $purchaseOrder): ?User
    {
        if ($purchaseOrder->relationLoaded('requestedByUser')) {
            $user = $purchaseOrder->getRelation('requestedByUser');
            if ($user) {
                return $user;
            }
        }

        $user = $purchaseOrder->requestedByUser;
        if ($user) {
            return $user;
        }

        if ($purchaseOrder->relationLoaded('assignedUser')) {
            return $purchaseOrder->getRelation('assignedUser');
        }

        return $purchaseOrder->assignedUser;
    }

    protected function userTeamIds(User $user): array
    {
        $ids = [];
        if (method_exists($user, 'teams')) {
            try {
                $ids = $user->teams->pluck('id')->map(fn($id) => (int) $id)->all();
            } catch (\Throwable $e) {
                $ids = [];
            }
        }

        if (empty($ids) && isset($user->team_id)) {
            $ids = array_filter([(int) $user->team_id]);
        }

        return $ids;
    }

    protected function hasAnyScope(User $user, string $action): bool
    {
        return $user->can("{$this->prefix}.{$action}.company")
            || $user->can("{$this->prefix}.{$action}.department")
            || $user->can("{$this->prefix}.{$action}.team")
            || $user->can("{$this->prefix}.{$action}.own");
    }
}
