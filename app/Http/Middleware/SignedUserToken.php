<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SignedUserToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = (int) $request->header('X-Api-User', $request->query('user_id'));
        $expires = (int) $request->header('X-Api-Expires', $request->query('expires'));
        $signature = (string) $request->header('X-Api-Signature', $request->query('signature'));
        $ttlMinutes = (int) config('app.api_auth_ttl_minutes', 720);

        if ($userId <= 0 || $expires <= 0 || $signature === '') {
            abort(401);
        }

        if (time() > $expires) {
            abort(401);
        }
        if ($ttlMinutes > 0 && $expires - time() > ($ttlMinutes * 60)) {
            abort(401);
        }

        $expected = hash_hmac('sha256', $this->signingPayload($userId, $expires), $this->signingKey());
        if (!hash_equals($expected, $signature)) {
            abort(401);
        }

        if (!Auth::onceUsingId($userId)) {
            abort(401);
        }

        return $next($request);
    }

    private function signingPayload(int $userId, int $expires): string
    {
        return $userId . '|' . $expires;
    }

    private function signingKey(): string
    {
        $key = (string) config('app.key');
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7)) ?: '';
        }

        return $key;
    }
}
