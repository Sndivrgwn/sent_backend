<?php

namespace App\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BroadcastMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageData;

    public function __construct(array $messageData)
    {
        $this->messageData = $messageData;
    }

    public function broadcastOn()
    {
        return new Channel("broadcast-chat-channel");
    }

    public function broadcastAs()
    {
        return 'broadcast-message-sent';
    }

    public function broadcastWith()
    {
        $createdAt = Carbon::parse($this->messageData['created_at'])->setTimezone('Asia/Jakarta');
        
        return [
            'broadcast_id' => $this->messageData['broadcast_id'],
            'sender_id' => $this->messageData['sender_id'],
            'sender_name' => $this->messageData['sender_name'],
            'message_text' => $this->messageData['message_text'],
            'time' => $createdAt->format('H:i'),
            'date' => $createdAt->format('Y-m-d, D'),
        ];
    }
}