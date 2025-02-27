<?php

use App\Http\Controllers\Api\LoginController as ApiLoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Auth\authController as AuthAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\user\LoginController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Pusher\Pusher;

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
Broadcast::routes(['middleware' => ['auth:sanctum']]);
 

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

/**
 * route "/messages"
 * @method "POST"
 */

    Route::middleware('auth:sanctum')->group(function() {
        Route::post('/chat', [ChatController::class, 'sendMessage']);
        Route::get('/chat/{userId}', [ChatController::class, 'getMessages']);
        Route::post('/chat/messages/mark-as-read', [ChatController::class, 'markAsRead']);
        Route::get('/contact', [ChatController::class, 'getChatContacts']);
        Route::put('/chat/message/{messageId}', [ChatController::class, 'editMessage']);
        Route::delete('/chat/message/{messageId}', [ChatController::class, 'deleteSingleChat']);
        Route::delete('/chat/{userId}', [ChatController::class, 'deleteChatWithUser']);
        Route::post('/chat/group', [GroupChatController::class, 'createGroup']);
        Route::post('/chat/group/message', [GroupChatController::class, 'sendGroupMessage']);
        Route::get('/chat/group/{groupId}', [GroupChatController::class, 'getGroupMessages']);
        Route::get('/group-contacts', [GroupChatController::class, 'getGroupContacts']);
        Route::post('/chat/broadcast/create', [BroadcastController::class, 'createBroadcast']); // Menyimpan daftar penerima
        Route::get('/chat/broadcast/list', [BroadcastController::class, 'getCreatedBroadcasts']);
        Route::post('/chat/broadcast', [BroadcastController::class, 'sendBroadcastMessage']);
        Route::get('/chat/broadcast/{broadcast_id}', [BroadcastController::class, 'getBroadcastMessages']);
    });
    
    
    
    Route::post('/register', [AuthAuthController::class, 'register']);
    Route::post('/login', [AuthAuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthAuthController::class, 'logout']);
    Route::put('/update-image-group/{id}', [ImageController::class, 'updateGroupImg']);

    Route::get('/users/{id}', [AuthAuthController::class, 'getUserById']);
    Route::put('/users/{id}', [UserController::class, 'update']);

    
/**
//  * route "/register"==
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
    $user = $request->user();

    // Modifikasi properti img agar mengembalikan URL lengkap
    if ($user && $user->img) {
        $user->img = asset('storage/' . $user->img);
    }

    return response()->json($user);
});



/**
 * route "/logout"
 * @method "POST"
 */

// Route::post('/logout', LogoutController::class)->name('logout');

// Oauth

Route::get('/auth/redirect/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/callback/google', [AuthController::class, 'handleGoogleCallback']);