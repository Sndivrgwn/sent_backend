<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;

    /**
     * Create a new event instance.
     */
    public function __construct($chat)
    {
        $this->chat = $chat;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return ['chat-channel'];
    }

    public function broadcastAs()
    {
        return 'message-sent';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->chat->id,
            'sender_id' => $this->chat->sender_id,
            'sender_name' => $this->chat->sender->name,
            'receiver_id' => $this->chat->receiver_id,
            'receiver_name' => $this->chat->receiver->name,
            'message_text' => $this->chat->message_text,
            'time' => $this->chat->created_at->setTimezone('Asia/Jakarta')->format('H:i'),
            'date' => $this->chat->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d, D'),
            'is_read' => $this->chat->is_read,
        ];
    }
}
