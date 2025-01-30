<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupChatController extends Controller
{
    public function createGroup(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'members' => 'required|array|min:2', // Minimal 2 anggota (tidak termasuk pembuat grup)
            'members.*' => 'exists:users,id'
        ]);

        // Buat grup chat baru
        $group = ChatGroup::create([
            'name' => $validatedData['name'],
            'created_by' => Auth::id(),
        ]);

        // Tambahkan pembuat grup sebagai anggota
        ChatGroupMember::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
        ]);

        // Tambahkan anggota lain ke dalam grup
        foreach ($validatedData['members'] as $memberId) {
            ChatGroupMember::create([
                'group_id' => $group->id,
                'user_id' => $memberId,
            ]);
        }

        return response()->json(['message' => 'Group created successfully', 'group' => $group]);
    }

    public function sendGroupMessage(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'group_id' => 'required|exists:chat_groups,id',
            'message_text' => 'required|string|max:1000',
        ]);

        $group = ChatGroup::find($validatedData['group_id']);

        // Pastikan user adalah anggota grup
        $isMember = ChatGroupMember::where('group_id', $group->id)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        // Simpan pesan ke database
        $chat = ChatMessage::create([
            'sender_id' => Auth::id(),
            'group_id' => $group->id,
            'message_text' => $validatedData['message_text'],
        ]);

        // Broadcast event untuk real-time (opsional)
        event(new MessageSent($chat));

        return response()->json(['message' => 'Message sent successfully', 'data' => $chat]);
    }

    public function getGroupMessages($groupId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $messages = ChatMessage::where('group_id', $groupId)
            ->with('sender:id,name,email')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function getGroupContacts()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $currentUserId = Auth::id();

        // Ambil semua grup yang diikuti oleh user yang sedang login
        $groups = ChatGroup::whereHas('members', function ($query) use ($currentUserId) {
            $query->where('user_id', $currentUserId);
        })->get();

        // Ambil pesan terakhir dalam setiap grup
        $groupContacts = $groups->map(function ($group) {
            $lastMessage = ChatGroupMember::where('group_id', $group->id)->latest()->first();

            return [
                'group_id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'last_message' => $lastMessage ? $lastMessage->message_text : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : 'Never',
            ];
        });

        return response()->json($groupContacts);
    }
}
