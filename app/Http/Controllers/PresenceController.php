<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PresenceController extends Controller
{
    public function heartbeat(Request $request)
    {
        $start = microtime(true);
        $observe = (bool) config('app.observe_lightweight_endpoints', false);

        $user = $request->user();
        if ($observe) {
            Log::info('presence.heartbeat.start', [
                'user_id' => $user?->id,
                'user_id_count' => 1,
            ]);
        }
        $user->forceFill(['last_seen_at' => now()])->save();
        $now = now();

        $cache = $this->cacheStore();
        $cache->put($this->presenceCacheKey($user->id), [
            'last_seen_at' => $now->toIso8601String(),
        ], now()->addSeconds(10));

        if ($observe) {
            Log::info('presence.heartbeat', [
                'user_id' => $user->id,
                'user_id_count' => 1,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);
        }

        return response()->json([
            'ok' => true,
            'server_time' => $now->toIso8601String(),
        ]);
    }

    public function status(Request $request)
    {
        $start = microtime(true);
        $observe = (bool) config('app.observe_lightweight_endpoints', false);

        $data = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer', 'distinct'],
        ]);

        $now = now();
        $userIds = array_values(array_unique($data['user_ids']));
        if ($observe) {
            Log::info('presence.status.start', [
                'user_id' => $request->user()?->id,
                'user_id_count' => count($userIds),
            ]);
        }

        $cache = $this->cacheStore();
        $cacheKeys = [];
        foreach ($userIds as $userId) {
            $cacheKeys[$userId] = $this->presenceCacheKey($userId);
        }

        $cached = $cache->many(array_values($cacheKeys));
        $cachedPayloads = [];
        $missingIds = [];

        foreach ($cacheKeys as $userId => $key) {
            $payload = $cached[$key] ?? null;
            if (is_array($payload)) {
                $cachedPayloads[$userId] = $payload;
            } else {
                $missingIds[] = $userId;
            }
        }

        if (!empty($missingIds)) {
            $users = User::query()
                ->whereIn('id', $missingIds)
                ->get(['id', 'last_seen_at']);

            foreach ($users as $user) {
                $payload = [
                    'last_seen_at' => $user->last_seen_at?->toIso8601String(),
                ];
                $cachedPayloads[$user->id] = $payload;
                $cache->put($this->presenceCacheKey($user->id), $payload, now()->addSeconds(10));
            }
        }

        $status = collect($userIds)->mapWithKeys(function (int $userId) use ($now, $cachedPayloads) {
            $payload = $cachedPayloads[$userId] ?? ['last_seen_at' => null];
            $lastSeenAt = $payload['last_seen_at'] ? Carbon::parse($payload['last_seen_at']) : null;
            $isOnline = $lastSeenAt
                ? $lastSeenAt->diffInSeconds($now) <= 30
                : false;

            return [
                $userId => [
                    'is_online' => $isOnline,
                    'last_seen_at' => $lastSeenAt?->toIso8601String(),
                ],
            ];
        });

        if ($observe) {
            Log::info('presence.status', [
                'user_id' => $request->user()?->id,
                'user_id_count' => count($userIds),
                'requested_ids' => count($userIds),
                'cache_hits' => count($userIds) - count($missingIds),
                'cache_misses' => count($missingIds),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);
        }

        return response()->json([
            'data' => $status,
            'server_time' => $now->toIso8601String(),
        ]);
    }

    private function cacheStore()
    {
        static $resolvedStore = null;
        if ($resolvedStore) {
            return Cache::store($resolvedStore);
        }

        if (config('cache.stores.redis')) {
            try {
                $cache = Cache::store('redis');
                $cache->get('__presence_cache_probe__');
                $resolvedStore = 'redis';
                return $cache;
            } catch (\Throwable $e) {
                // fall through to file
            }
        }

        $resolvedStore = 'file';
        return Cache::store('file');
    }

    private function presenceCacheKey(int $userId): string
    {
        return 'presence:last_seen:' . $userId;
    }
}
