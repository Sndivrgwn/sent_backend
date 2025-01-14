<?php

use App\Http\Controllers\Api\LoginController as ApiLoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Auth\AuthController as AuthAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\user\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

/**
 * route "/messages"
 * @method "POST"
 */

    Route::middleware('auth:sanctum')->group(function() {
        Route::post('/chat', [ChatController::class, 'sendMessage']);
        Route::get('/chat', [ChatController::class, 'getMessages']);
    });



    Route::post('/register', [AuthAuthController::class, 'register']);
    Route::post('/login', [AuthAuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthAuthController::class, 'logout']);

    Route::get('/users/{id}', [AuthAuthController::class, 'getUserById']);

/**
//  * route "/register"
//  * @method "POST"
//  */
// Route::post('/register', RegisterController::class)->name('register');

// /**
//  * @method "POST"
//  */
// Route::post('/login', ApiLoginController::class)->name('login');

/**
 * route "/user"
 * @method "GET"
 */
// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



/**
 * route "/logout"
 * @method "POST"
 */

// Route::post('/logout', LogoutController::class)->name('logout');

// Oauth

Route::get('/auth/redirect/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/callback/google', [AuthController::class, 'handleGoogleCallback']);