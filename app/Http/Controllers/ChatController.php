<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Models\ChatMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    // Mengirim pesan private
    public function sendMessage(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'receiver_id' => 'required|exists:users,id', // Pastikan penerima ada di database
            'message_text' => 'required|string|max:1000', // Validasi isi pesan
        ]);

        // Buat pesan baru
        $chat = ChatMessage::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $validatedData['receiver_id'],
            'message_text' => $validatedData['message_text'],
        ]);

        // Broadcast event untuk real-time
        event(new MessageSent($chat));

        return response()->json([
            'status' => 'Message Sent!',
            'sender_name' => Auth::user()->name,
            'message' => $chat,
        ], 201);
    }

    // Mengambil riwayat pesan antara sender dan receiver (private chat)
    public function getMessages($receiverId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $messages = ChatMessage::where(function ($query) use ($receiverId) {
            $query->where('sender_id', Auth::id())
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($receiverId) {
            $query->where('sender_id', $receiverId)
                ->where('receiver_id', Auth::id());
        })->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Format respons
        return response()->json($messages->map(function ($message) {
            $jakartaTimezone = 'Asia/Jakarta';
            $createdAtInJakarta = $message->created_at->setTimezone($jakartaTimezone);

            return [
                'message_id' => $message->id,
                'sender_id' => $message->sender->id,
                'sender_name' => $message->sender->name,
                'receiver_id' => $message->receiver->id,
                'receiver_name' => $message->receiver->name,
                'message_text' => $message->message_text,
                'time' => $createdAtInJakarta->format('H:i'),
                'date' => $createdAtInJakarta->format('Y-m-d, D'),
                'is_read' => $message->is_read,
            ];
        }));
    }

    public function deleteSingleChat($messageId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $currentUserId = Auth::id();

        $chatMessage = ChatMessage::where('id', $messageId)
            ->where(function ($query) use ($currentUserId) {
                $query->where('sender_id', $currentUserId)
                    ->orWhere('receiver_id', $currentUserId);
            })
            ->first();

        if (!$chatMessage) {
            return response()->json(['error' => 'Message not found or unauthorized'], 404);
        }

        $receiverId = $chatMessage->receiver_id;

        $chatMessage->delete();

        event(new MessageDeleted($messageId, $receiverId));

        return response()->json(['message' => 'Chat message deleted successfully']);
    }


    public function deleteChatWithUser($userId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $currentUserId = Auth::id();

        // Hapus semua pesan antara user yang sedang login dengan user lain
        ChatMessage::where(function ($query) use ($currentUserId, $userId) {
            $query->where('sender_id', $currentUserId)->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($currentUserId, $userId) {
            $query->where('sender_id', $userId)->where('receiver_id', $currentUserId);
        })->delete();

        return response()->json(['message' => 'Chat deleted successfully']);
    }

    // Mengambil daftar kontak dengan siapa pengguna pernah mengirim atau menerima pesan
    public function getChatContacts()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $currentUserId = Auth::id();

        // Ambil semua user kecuali user yang sedang login
        $users = User::where('id', '!=', $currentUserId)->get();

        // Ambil pesan terakhir antara user yang login dengan setiap user lain
        $contacts = $users->map(function ($user) use ($currentUserId) {
            $lastMessage = ChatMessage::where(function ($query) use ($currentUserId, $user) {
                $query->where('sender_id', $currentUserId)->where('receiver_id', $user->id);
            })->orWhere(function ($query) use ($currentUserId, $user) {
                $query->where('sender_id', $user->id)->where('receiver_id', $currentUserId);
            })->latest()->first();

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'divisi' => $user->divisi,
                'kelas' => $user->kelas,
                'img' => asset('storage/' . $user->img),
                'last_message' => $lastMessage ? $lastMessage->message_text : null,
                'last_online' => $user->last_online ? Carbon::parse($user->last_online)->diffForHumans() : 'Never',
            ];
        });

        return response()->json($contacts);
    }


public function markAsRead(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $validatedData = $request->validate([
        'receiver_id' => 'required|exists:users,id',
    ]);

    // Debug: Cek apakah ada pesan yang belum dibaca
    $messages = ChatMessage::where('receiver_id', Auth::id())
        ->where('sender_id', $validatedData['receiver_id'])
        ->where('is_read', false)
        ->get();

    Log::info("Pesan yang akan ditandai sebagai read:", $messages->toArray()); // Tambahkan log

    if ($messages->isEmpty()) {
        return response()->json(['status' => 'No messages to update']);
    }

    // Update status pesan menjadi is_read = true
    ChatMessage::whereIn('id', $messages->pluck('id'))->update(['is_read' => true]);

    // Kirim event dengan semua message_id yang telah diupdate
    event(new MessageRead(Auth::id(), $validatedData['receiver_id'], $messages->pluck('id')->toArray()));

    return response()->json(['status' => 'Messages marked as read']);
}





    public function editMessage(Request $request, $messageId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'message_text' => 'required|string|max:1000',
        ]);

        $currentUserId = Auth::id();

        $chatMessage = ChatMessage::where('id', $messageId)
            ->where('sender_id', $currentUserId)
            ->first();

        if (!$chatMessage) {
            return response()->json(['error' => 'Message not found or unauthorized'], 404);
        }

        $chatMessage->message_text = $validatedData['message_text'];
        $chatMessage->save();

        event(new MessageUpdated($chatMessage));

        return response()->json(['message' => 'Chat message updated successfully', 'updated_message' => $chatMessage]);
    }
}
