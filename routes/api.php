<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\SmsWebhookController;

Route::post('/leads', [LeadController::class, 'store']);
Route::post('/service-requests', [ServiceRequestController::class, 'store']);

// SMS Delivery Report (DLR) webhook for Faraz Edge (IPPANEL)
Route::post('/webhooks/sms/faraz-edge', [SmsWebhookController::class, 'farazEdge'])
    ->name('webhooks.sms.faraz_edge');
