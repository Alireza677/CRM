<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\SmsWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| این فایل برای تعریف مسیرهای API استفاده می‌شود.
| همه مسیرهای اینجا به صورت پیش‌فرض تحت /api اجرا می‌شن.
| مثلاً مسیر /api/leads در دسترس خواهد بود.
|
*/

Route::post('/leads', [LeadController::class, 'store']);

// SMS Delivery Report (DLR) webhook for Faraz Edge (IPPANEL)
Route::post('/webhooks/sms/faraz-edge', [SmsWebhookController::class, 'farazEdge'])
    ->name('webhooks.sms.faraz_edge');
