<?php

namespace App\Http\Controllers;

use App\Notifications\TestWebPushNotification;
use Illuminate\Http\Request;

class WebPushTestController extends Controller
{
    public function index()
    {
        return view('dev.push-test');
    }

    public function send(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user->notify(new TestWebPushNotification());

        return response()->json(['ok' => true]);
    }
}
