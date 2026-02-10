<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebPushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'publicKey' => ['nullable', 'string'],
            'authToken' => ['nullable', 'string'],
            'contentEncoding' => ['nullable', 'string'],
        ]);

        $user->updatePushSubscription(
            $data['endpoint'],
            $data['publicKey'] ?? null,
            $data['authToken'] ?? null,
            $data['contentEncoding'] ?? null
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $user->deletePushSubscription($data['endpoint']);

        return response()->json(['ok' => true]);
    }
}
