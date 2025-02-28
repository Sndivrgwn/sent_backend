<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use SerializesModels;

    public $receiverId, $senderId, $messageIds;

    public function __construct($receiverId, $senderId, $messageIds)
    {
        $this->receiverId = $receiverId;
        $this->senderId = $senderId;
        $this->messageIds = $messageIds; // Tambahkan message_id
    }

    public function broadcastOn()
    {
        return new Channel("chat-channel");
    }

    public function broadcastAs()
    {
        return 'message-read';
    }

    public function broadcastWith()
    {
        return [
            'receiver_id' => $this->receiverId,
            'sender_id' => $this->senderId,
            'message_ids' => $this->messageIds, // Kirim array message_id
        ];
    }
}
