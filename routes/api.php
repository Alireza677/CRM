<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;

Route::post('/leads', [LeadController::class, 'store']);
