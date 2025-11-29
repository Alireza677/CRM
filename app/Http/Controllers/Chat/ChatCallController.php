<?php

namespace App\Http\Controllers\Chat;

use App\Events\ChatGroupCallStarted;
use App\Http\Controllers\Controller;
use App\Models\OnlineChatGroup;
use App\Models\OnlineMeeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatCallController extends Controller
{
    public function startVideoCall(OnlineChatGroup $group): JsonResponse
    {
        $user = Auth::user();

        $isMember = $group->memberships()
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'دسترسی به این گروه برای شما مجاز نیست.');
        abort_if(!$group->is_active, 403, 'این گروه غیرفعال است.');

        $roomName = $this->generateRoomName($group->id);

        $meeting = OnlineMeeting::create([
            'title' => 'تماس گروهی: ' . $group->title,
            'scheduled_at' => now(),
            'duration_minutes' => 60,
            'notes' => null,
            'related_type' => OnlineChatGroup::class,
            'related_id' => $group->id,
            'online_chat_group_id' => $group->id,
            'room_name' => $roomName,
            'jitsi_url' => 'https://meet.jit.si/' . $roomName,
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        broadcast(new ChatGroupCallStarted($meeting, $group, $user));

        return response()->json([
            'data' => [
                'url' => $meeting->jitsi_url,
                'meeting_id' => $meeting->id,
            ],
        ]);
    }

    private function generateRoomName(int $groupId): string
    {
        $base = 'chat-' . $groupId . '-' . now()->format('YmdHis');
        $slug = Str::slug($base, '-', 'en') ?: 'chat-' . $groupId . '-' . Str::random(6);

        $room = $slug;
        $counter = 1;

        while (OnlineMeeting::withTrashed()->where('room_name', $room)->exists()) {
            $room = $slug . '-' . $counter;
            $counter++;
        }

        return $room;
    }
}
