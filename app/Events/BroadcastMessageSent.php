<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BroadcastMessageSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel("broadcast-chat-channel-{$this->message['broadcast_id']}");
    }

    public function broadcastAs()
    {
        return 'broadcast-message-sent';
    }
}
