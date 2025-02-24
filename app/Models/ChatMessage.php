<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'group_id',
        'message_text',
        'is_read',
        'is_broadcast'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    public function scopeBroadcast($query)
    {
        return $query->where('is_broadcast', true);
    }

    public function broadcast()
    {
        return $this->belongsTo(Broadcast::class, 'broadcast_id');
    }
}
