<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Pusher\Pusher;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class, 'index']);

require __DIR__.'/auth.php';

Route::post('/pusher/auth', function (Request $request) {
    if (Auth::check()) {
        return Pusher::authorizeChannel($request);
    }
    return response()->json(['error' => 'Unauthorized'], 403);
});