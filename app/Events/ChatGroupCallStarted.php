<?php

namespace App\Events;

use App\Models\OnlineChatGroup;
use App\Models\OnlineMeeting;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatGroupCallStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OnlineMeeting $meeting,
        public OnlineChatGroup $group,
        public User $starter
    ) {
        //
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.groups.' . $this->group->id);
    }

    public function broadcastAs(): string
    {
        return 'chat.call.started';
    }

    public function broadcastWith(): array
    {
        return [
            'meeting_id' => $this->meeting->id,
            'group_id' => $this->group->id,
            'group_title' => $this->group->title,
            'jitsi_url' => $this->meeting->jitsi_url,
            'room_name' => $this->meeting->room_name,
            'started_by' => [
                'id' => $this->starter->id,
                'name' => $this->starter->name,
                'email' => $this->starter->email,
            ],
            'started_at' => optional($this->meeting->scheduled_at)->toIso8601String(),
        ];
    }
}
