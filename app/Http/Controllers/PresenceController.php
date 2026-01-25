<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PresenceController extends Controller
{
    public function heartbeat(Request $request)
    {
        $user = $request->user();
        $user->forceFill(['last_seen_at' => now()])->save();

        return response()->json([
            'ok' => true,
            'server_time' => now()->toISOString(),
        ]);
    }

    public function status(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer', 'distinct'],
        ]);

        $now = now();
        $users = User::query()
            ->whereIn('id', $data['user_ids'])
            ->get(['id', 'last_seen_at']);

        $status = $users->mapWithKeys(function (User $user) use ($now) {
            $lastSeen = $user->last_seen_at;
            $isOnline = false;
            if ($lastSeen instanceof Carbon) {
                $isOnline = $lastSeen->diffInSeconds($now) <= 30;
            }

            return [
                $user->id => [
                    'is_online' => $isOnline,
                    'last_seen_at' => $lastSeen?->toISOString(),
                ],
            ];
        });

        return response()->json([
            'data' => $status,
            'server_time' => $now->toISOString(),
        ]);
    }
}
