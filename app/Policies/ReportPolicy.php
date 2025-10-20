<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        if (!$user) return false;
        // prevent disabled accounts
        if (array_key_exists('active', $user->getAttributes()) && !$user->active) {
            return false;
        }
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        if (array_key_exists('active', $user->getAttributes()) && !$user->active) {
            return false;
        }
        if ($report->created_by === $user->id) {
            return true;
        }
        if ($report->visibility === 'public') {
            return true;
        }
        if ($report->visibility === 'shared') {
            return $report->sharedUsers()->whereKey($user->id)->exists();
        }
        return false;
    }

    public function create(User $user): bool
    {
        return (bool)$user; // allow any authenticated user to create
    }

    public function update(User $user, Report $report): bool
    {
        if (array_key_exists('active', $user->getAttributes()) && !$user->active) {
            return false;
        }
        if ($report->created_by === $user->id) {
            return true;
        }
        if ($report->visibility === 'shared') {
            return $report->sharedUsers()
                ->whereKey($user->id)
                ->wherePivot('can_edit', true)
                ->exists();
        }
        return false;
    }

    public function delete(User $user, Report $report): bool
    {
        if (array_key_exists('active', $user->getAttributes()) && !$user->active) {
            return false;
        }
        // Admins can delete any report
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }
        // same rule as update
        return $this->update($user, $report);
    }

    public function share(User $user, Report $report): bool
    {
        if (array_key_exists('active', $user->getAttributes()) && !$user->active) {
            return false;
        }
        // sharing allowed for owner or shared member with can_edit
        return $this->update($user, $report);
    }
}
