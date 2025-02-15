<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GroupChatController extends Controller
{


    public function createGroup(Request $request)
    {
        Log::info('Create Group Function Called');
        Log::info('Request Data:', $request->all());  // Log the incoming request data

        try {
            // Validasi input
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'members' => 'required|array|min:2', // Ensure members is an array
                'members.*' => 'integer', // Validate that each member is an integer
            ]);

            // Ensure members is an array, in case it's sent as a JSON string
            $members = is_array($validatedData['members']) ? $validatedData['members'] : json_decode($validatedData['members']);

            Log::info('Validated Data:', $validatedData);

            // Buat grup chat baru
            $group = ChatGroup::create([
                'name' => $validatedData['name'],
                'created_by' => Auth::id(),
            ]);

            Log::info('Group Created:', $group->toArray());

            // Tambahkan pembuat grup sebagai anggota
            ChatGroupMember::create([
                'group_id' => $group->id,
                'user_id' => Auth::id(),
            ]);

            Log::info('Group Creator Added as Member:', ['user_id' => Auth::id()]);

            // Tambahkan anggota lain ke dalam grup
            foreach ($members as $memberId) {
                ChatGroupMember::create([
                    'group_id' => $group->id,
                    'user_id' => $memberId,
                ]);

                Log::info('Member Added to Group:', ['user_id' => $memberId]);
            }

            Log::info('Group Creation Process Completed');

            return response()->json([
                'message' => 'Group created successfully',
                'group' => $group,
            ]);
        } catch (\Exception $e) {
            // Catat error ke log
            Log::error('Error in createGroup:', ['error' => $e->getMessage()]);

            // Kembalikan respons error
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function sendGroupMessage(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate the request
        $validatedData = $request->validate([
            'group_id' => 'required|exists:chat_groups,id',  // Ensure the group exists
            'message_text' => 'required|string|max:1000',    // Ensure the message text is valid
        ]);

        // Find the group by the validated group_id
        $group = ChatGroup::find($validatedData['group_id']);

        // Check if the user is a member of the group
        $isMember = ChatGroupMember::where('group_id', $group->id)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        // Create and save the message associated with the group and sender
        $chat = ChatMessage::create([
            'sender_id' => Auth::id(),         // The user sending the message
            'group_id' => $group->id,          // The group where the message is being sent
            'message_text' => $validatedData['message_text'], // The message content
        ]);

        // Optionally broadcast the message (real-time functionality)
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

        // Ambil pesan terakhir dalam setiap grup & daftar anggota
        $groupContacts = $groups->map(function ($group) {
            $lastMessage = ChatMessage::where('group_id', $group->id)
                ->latest()
                ->first();

            // Ambil anggota grup dengan join ke tabel users
            $members = ChatGroupMember::where('group_id', $group->id)
                ->join('users', 'chat_group_members.user_id', '=', 'users.id')
                ->select('users.id', 'users.name')
                ->get();

            return [
                'group_id' => $group->id,
                'img' => $group->img,
                'name' => $group->name,
                'description' => $group->description,
                'last_message' => $lastMessage ? $lastMessage->message_text : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : 'Never',
                'members' => $members, // Mengembalikan daftar anggota grup dengan ID & nama
            ];
        });

        return response()->json($groupContacts);
    }
}
