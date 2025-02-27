<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'created_by', 'img'];

    public function members()
    {
        return $this->hasMany(ChatGroupMember::class, 'group_id');
    }
}
