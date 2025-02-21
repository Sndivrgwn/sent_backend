<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    use HasFactory;

    protected $fillable = ['sender_id', 'recipient_ids'];

    protected $casts = [
        'recipient_ids' => 'array', // Konversi JSON ke array otomatis
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
