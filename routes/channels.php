<?php

use App\Models\ChatGroupMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('group-chat.{group_id}', function ($user, $group_id) {
    return ChatGroupMember::where('group_id', $group_id)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('private-chat-channel.{userId}', function ($user, $userId) {
    Log::info("🔍 User mencoba subscribe:", [
        'user' => $user->id ?? 'Guest',
        'channel_userId' => $userId
    ]);

    return (int) $user->id === (int) $userId;
}, ['guards' => ['api']]); // 🔥 Pastikan hanya menggunakan guard API













