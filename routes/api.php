<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\SmsWebhookController;
use App\Http\Controllers\Api\PhoneCallWebhookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PresenceController;
use App\Http\Middleware\SignedUserToken;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/*
|--------------------------------------------------------------------------
| Public API (no auth)
|--------------------------------------------------------------------------
*/

Route::post('/leads', [LeadController::class, 'store']);
Route::post('/service-requests', [ServiceRequestController::class, 'store']);

// SMS Delivery Report (DLR) webhook for Faraz Edge (IPPANEL)
Route::post('/webhooks/sms/faraz-edge', [SmsWebhookController::class, 'farazEdge'])
    ->name('webhooks.sms.faraz_edge');

// Phone call webhook
Route::post('/phone-calls/webhook', [PhoneCallWebhookController::class, 'store'])
    ->name('phone-calls.webhook');


/*
|--------------------------------------------------------------------------
| Signed user API (stateless, no session)
|--------------------------------------------------------------------------
*/

Route::middleware([
        SignedUserToken::class,
        'throttle:120,1',

    ])
    ->withoutMiddleware([
        EnsureFrontendRequestsAreStateful::class,
    ])
    ->group(function () {

        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
            ->name('notifications.unreadCount');

        Route::prefix('presence')->name('presence.')->group(function () {
            Route::post('heartbeat', [PresenceController::class, 'heartbeat'])
                ->name('heartbeat');

            Route::get('status', [PresenceController::class, 'status'])
                ->name('status');
        });
    });

/*
|--------------------------------------------------------------------------
| Middleware test route (local only)
|--------------------------------------------------------------------------
*/

Route::middleware(SignedUserToken::class)->get('/_mw_test', function () {
    return response()->json([
        'ok' => true,
        'user_id' => auth()->id(),
    ]);
});
