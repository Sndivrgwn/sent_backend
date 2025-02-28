<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'img',
        'description',
    ];

    // Relasi ke user yang membuat grup
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke anggota grup
    public function members()
    {
        return $this->hasMany(ChatGroupMember::class, 'group_id');
    }

    // Relasi ke pesan dalam grup
    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'group_id');
    }
}