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

class MessageUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel("chat-channel");
    }

    public function broadcastAs()
    {
        return 'message-updated';
    }

    public function broadcastWith()
{
    return [
        'message_id' => $this->message->id,
        'message_text' => $this->message->message_text, // Pesan yang telah diperbarui
        'updated_at' => $this->message->updated_at->toISOString(), // Waktu update
    ];
}
}
