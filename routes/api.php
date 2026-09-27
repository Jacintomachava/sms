<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SmsController;
use App\Http\Controllers\Api\V1\SenderController;
use App\Http\Controllers\Api\V1\BalanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->middleware('api.key')->group(function () {

    Route::post('/sms/send',[SmsController::class, 'send']);
    Route::post('/sms/send-many',[SmsController::class, 'sendMany']);
    Route::post('/sms/bulk',[SmsController::class, 'bulk']);
    Route::get('/sms',[SmsController::class, 'index']);
    Route::get('/sms/{id}',[SmsController::class, 'show']);
    Route::get('/senders',[SenderController::class, 'index']);
    Route::get('/balance',[BalanceController::class, 'show']);

});
