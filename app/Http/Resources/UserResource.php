<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ChatGroupMember;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        // Ambil daftar grup yang diikuti user beserta rolenya
        $groups = ChatGroupMember::where('user_id', $this->id)
            ->with('group')
            ->get()
            ->map(function ($member) {
                return [
                    'group_id' => $member->group_id,
                    'group_name' => $member->group->name ?? null, // Pastikan model ChatGroup memiliki kolom name
                    'role' => $member->role,
                ];
            });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'img' => $this->img ? asset('storage/' . $this->img) : null,
            'groups' => $groups, // Menyertakan daftar grup dan role dalam respons
        ];
    }
}

