<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\TelephonyWebhookEvent;

class WebhookEventController extends Controller
{
    public function index()
    {
        $events = TelephonyWebhookEvent::query()
            ->orderByDesc('received_at')
            ->limit(50)
            ->get();

        return view('telephony.webhook_events.index', compact('events'));
    }
}
