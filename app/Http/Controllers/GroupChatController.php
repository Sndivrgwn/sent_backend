<?php

namespace App\Http\Controllers;

use App\Events\GroupMessage;
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
        // Validate input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'members' => 'required|array|min:2', // Ensure members is an array
            'members.*' => 'integer', // Validate that each member is an integer
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate the image file
        ]);

        // Ensure members is an array, in case it's sent as a JSON string
        $members = is_array($validatedData['members']) ? $validatedData['members'] : json_decode($validatedData['members']);

        Log::info('Validated Data:', $validatedData);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Unique image name
            $imagePath = $image->storeAs('group_images/', $imageName, 'public'); // Save to public storage
        }

        // Create a new chat group
        $group = ChatGroup::create([
            'name' => $validatedData['name'],
            'created_by' => Auth::id(),
            'img' => $imagePath, // Save the image path to the database
        ]);

        Log::info('Group Created:', $group->toArray());

        // Add the group creator as a member
        ChatGroupMember::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
        ]);

        Log::info('Group Creator Added as Member:', ['user_id' => Auth::id()]);

        // Add other members to the group
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
        // Log the error
        Log::error('Error in createGroup:', ['error' => $e->getMessage()]);

        // Return an error response
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

    $validatedData = $request->validate([
        'group_id' => 'required|exists:chat_groups,id',
        'message_text' => 'required|string|max:1000',
    ]);

    $group = ChatGroup::find($validatedData['group_id']);

    $isMember = ChatGroupMember::where('group_id', $group->id)
        ->where('user_id', Auth::id())
        ->exists();

    if (!$isMember) {
        return response()->json(['error' => 'You are not a member of this group'], 403);
    }

    $chat = ChatMessage::create([
        'sender_id' => Auth::id(),
        'group_id' => $group->id,
        'message_text' => $validatedData['message_text'],
    ]);

    $user = Auth::user();

    Log::info("Mengirim event GroupMessage untuk grup ID: " . $group->id);
    event(new GroupMessage($chat));
    Log::info("Event berhasil dikirim");
    

    return response()->json([
        'message' => 'Message sent successfully',
        'data' => [
            'chat' => $chat,
            'user_name' => $user->name,
        ]
    ]);
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
                'img' => asset('storage/' . $group->img),
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
