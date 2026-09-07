<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\ApiRegisterController;
use App\Http\Controllers\ApiPostController;
use App\Http\Controllers\ApiOTPCodeController;
use App\Http\Controllers\ApiUserController;
use App\Http\Controllers\PostTypeController;
use App\Http\Controllers\ApiUserCategoryController;
use App\Http\Controllers\UserCategoryController;
use App\Http\Controllers\ApiPostTypeController;
use App\Http\Controllers\LastOtpController;
use App\Http\Controllers\ApiPostScoreController;
use App\Http\Controllers\ApiPostPaidVoteController;
use App\Http\Controllers\ApiPacakgeController;
use App\Http\Controllers\ApiPaymentFormController;
use App\Http\Controllers\ApiRechargeController;


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


//posts of files
 Route::post('/post/create', [ApiPostController::class, 'store']);
 Route::get('/post/list/{filter}',[ApiPostController::class, 'listPosts']);


// Authentication
Route::post('/verify-otp', [ApiOTPCodeController::class, 'verifyOTP']);
Route::post('/capture-phone', [ApiUserController::class, 'capturePhoneNumber']);

// Update User
Route::put('/users/{id}', [ApiUserController::class, 'updateUser']);
Route::get('/user/{user_id}', [ApiUserController::class, 'getDates']);  //voltar

//categorias por usuario
Route::post('/user-categories', [UserCategoryController::class, 'store']);  
Route::get('/user-categories/{user_id}', [UserCategoryController::class, 'show']);


Route::post('/post-types', [PostTypeController::class, 'index']);

//Icons
Route::get('/list/post-type', [ApiPostTypeController::class, 'index']);

//Last OTP
Route::get('/last-otp', [LastOtpController::class, 'getLastOtp']);

//Votacao
Route::get('/score/post/{post_id}', [ApiPostScoreController::class, 'getScore']);
Route::get('/vote/post/{post_id}', [APiPostPaidVoteController::class, 'getPostVote']);
Route::post('/create/vote', [ApiPostPaidVoteController::class, 'insertVotePaid']);


//Recargas
Route::get('list/packages', [ApiPacakgeController::class, 'index']);
Route::get('list/payment-forms', [ApiPaymentFormController::class, 'index']);
Route::get('list/recharges', [ApiRechargeController::class, 'index']);

