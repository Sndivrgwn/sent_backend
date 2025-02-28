<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use SerializesModels;

    public $messageId, $receiverId;

    public function __construct($messageId, $receiverId)
    {
        $this->messageId = $messageId;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn()
    {
        return new Channel("chat-channel");
    }

    public function broadcastAs()
    {
        return 'message-deleted';
    }

    public function broadcastWith()
{
    return [
        'message_id' => $this->messageId, // ID pesan yang dihapus
        'broadcast_id' => $this->receiverId, // ID penerima
    ];
}

}