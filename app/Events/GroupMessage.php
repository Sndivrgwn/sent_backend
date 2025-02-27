<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('group-chat.' . $this->message->group_id);
    }

    public function broadcastAs()
    {
        return 'group-message-sent';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'message_text' => $this->message->message_text,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name, // Tambahkan ini
            'created_at' => $this->message->created_at->format('H:i:s'),
        ];
    }
}
