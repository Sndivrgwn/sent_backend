<?php

namespace App\Providers;

use App\Models\ChatGroupMember;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
{
    Broadcast::routes(['middleware' => ['auth:sanctum']]);


    Broadcast::channel('group-chat.{group_id}', function ($user, $group_id) {
        Log::info('Authenticating user for channel:', [
            'user_id' => $user->id,
            'group_id' => $group_id,
        ]);

        return ChatGroupMember::where('group_id', $group_id)
            ->where('user_id', $user->id)
            ->exists();
    });
}
}
