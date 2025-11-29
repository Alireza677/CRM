<?php

namespace App\Policies;

use App\Models\OnlineChatGroup;
use App\Models\OnlineChatMembership;
use App\Models\User;

class OnlineChatGroupPolicy
{
    public function view(User $user, OnlineChatGroup $group): bool
    {
        return $group->memberships()->where('user_id', $user->id)->exists();
    }

    public function sendMessage(User $user, OnlineChatGroup $group): bool
    {
        return $group->is_active && $this->view($user, $group);
    }

    public function update(User $user, OnlineChatGroup $group): bool
    {
        return $this->manageMembers($user, $group);
    }

    public function manageMembers(User $user, OnlineChatGroup $group): bool
    {
        return in_array($group->memberRole($user), [
            OnlineChatMembership::ROLE_OWNER,
            OnlineChatMembership::ROLE_ADMIN,
        ], true);
    }

    public function delete(User $user, OnlineChatGroup $group): bool
    {
        return $this->manageMembers($user, $group);
    }
}
