<?php

namespace App\Http\Controllers;

use App\Events\GroupMessage;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                ->select(
                    'users.id',
                    'users.name',
                    'users.divisi',
                    'users.email',
                    DB::raw('CONCAT("' . asset('storage/') . '", users.img) as img'), // Menghasilkan URL lengkap
                    'users.kelas'
                )
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

    public function getGroupById($id)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $currentUserId = Auth::id();

    // Cari grup berdasarkan ID dan pastikan user yang login adalah anggota
    $group = ChatGroup::where('id', $id)
        ->whereHas('members', function ($query) use ($currentUserId) {
            $query->where('user_id', $currentUserId);
        })->first();

    if (!$group) {
        return response()->json(['error' => 'Group not found or access denied'], 404);
    }

    // Ambil pesan terakhir dalam grup
    $lastMessage = ChatMessage::where('group_id', $group->id)
        ->latest()
        ->first();

    // Ambil anggota grup dengan join ke tabel users
    $members = ChatGroupMember::where('group_id', $group->id)
        ->join('users', 'chat_group_members.user_id', '=', 'users.id')
        ->select(
            'users.id',
            'users.name',
            'users.divisi',
            'users.email',
            DB::raw('CONCAT("' . asset('storage/') . '", "/", REPLACE(users.img, "storage/", "")) as img'),
            'users.kelas'
        )
        ->get();

    // Format created_at ke tanggal/bulan/tahun jam:menit
    $createdAtFormatted = $group->created_at->format('d/m/Y H:i');

    // Ambil informasi owner (pembuat grup)
    $owner = $group->creator;

    $groupData = [
        'group_id' => $group->id,
        'img' => asset('storage/' . $group->img),
        'name' => $group->name,
        'description' => $group->description,
        'created_at' => $createdAtFormatted,
        'owner' => [
            'id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'img' => asset('storage/' . $owner->img),
        ],
        'last_message' => $lastMessage ? $lastMessage->message_text : null,
        'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : 'Never',
        'members' => $members,
    ];

    return response()->json($groupData);
}


    public function editGroup(Request $request, $groupId)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $validatedData = $request->validate([
        'name' => 'nullable|string|max:255',
        'description' => 'nullable|string', // Tambahkan validasi untuk description
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $group = ChatGroup::findOrFail($groupId);

    // Cek apakah user yang mengedit adalah pembuat grup
    if ($group->created_by !== Auth::id()) {
        return response()->json(['error' => 'You are not authorized to edit this group'], 403);
    }

    // Update nama grup jika ada
    if (isset($validatedData['name'])) {
        $group->name = $validatedData['name'];
    }

    // Update deskripsi grup jika ada
    if (isset($validatedData['description'])) {
        $group->description = $validatedData['description'];
    }

    // Update gambar grup jika ada
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . $image->getClientOriginalName();
        $imagePath = $image->storeAs('group_images', $imageName, 'public');
        $group->img = 'group_images/' . $imageName; // Simpan path relatif
    }

    $group->save();

    // Generate URL lengkap menggunakan asset()
    $group->img = asset('storage/' . $group->img);

    return response()->json([
        'message' => 'Group updated successfully',
        'group' => $group,
    ]);
}

    public function deleteGroup($groupId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $group = ChatGroup::findOrFail($groupId);

        // Cek apakah user yang menghapus adalah pembuat grup
        if ($group->created_by !== Auth::id()) {
            return response()->json(['error' => 'You are not authorized to delete this group'], 403);
        }

        // Hapus semua pesan yang terkait dengan grup
        ChatMessage::where('group_id', $groupId)->delete();

        // Hapus semua anggota grup
        ChatGroupMember::where('group_id', $groupId)->delete();

        // Hapus grup
        $group->delete();

        return response()->json([
            'message' => 'Group deleted successfully',
        ]);
    }

    public function addMember(Request $request, $groupId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);
    
        $group = ChatGroup::findOrFail($groupId);
    
        // Cek apakah user yang menambahkan member adalah pembuat grup
        if ($group->created_by !== Auth::id()) {
            return response()->json(['error' => 'You are not authorized to add members to this group'], 403);
        }
    
        // Cek apakah user sudah menjadi member
        $isMember = ChatGroupMember::where('group_id', $groupId)
            ->where('user_id', $validatedData['user_id'])
            ->exists();
    
        if ($isMember) {
            return response()->json(['error' => 'User is already a member of this group'], 400);
        }
    
        // Ambil data user yang akan ditambahkan
        $user = User::findOrFail($validatedData['user_id']);
    
        // Tambahkan member baru
        ChatGroupMember::create([
            'group_id' => $groupId,
            'user_id' => $validatedData['user_id'],
        ]);
    
        return response()->json([
            'message' => 'Member added successfully',
            'user' => $user, // Sertakan data user dalam respons
        ]);
    }

    public function removeMember(Request $request, $groupId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);
    
        $group = ChatGroup::findOrFail($groupId);
    
        // Cek apakah user yang menghapus member adalah pembuat grup
        if ($group->created_by !== Auth::id()) {
            return response()->json(['error' => 'You are not authorized to remove members from this group'], 403);
        }
    
        // Cek apakah user yang akan dihapus adalah pembuat grup
        if ($validatedData['user_id'] === $group->created_by) {
            return response()->json(['error' => 'You cannot remove the group creator'], 400);
        }
    
        // Ambil data user yang akan dihapus
        $user = User::findOrFail($validatedData['user_id']);
    
        // Hapus member dari grup
        ChatGroupMember::where('group_id', $groupId)
            ->where('user_id', $validatedData['user_id'])
            ->delete();
    
        return response()->json([
            'message' => 'Member removed successfully',
            'user' => $user, // Sertakan data user dalam respons
        ]);
    }
}
