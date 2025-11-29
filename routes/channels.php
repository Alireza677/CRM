<?php

use App\Models\OnlineChatMembership;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.groups.{groupId}', function (User $user, int $groupId) {
    return OnlineChatMembership::where('online_chat_group_id', $groupId)
        ->where('user_id', $user->id)
        ->exists();
});
