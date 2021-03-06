<?php

use Illuminate\Http\Request;
use App\Http\Controllers\ChatbotController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post(env('TELEGRAM_WEBHOOK_ROUTE'), ChatbotController::class);
