<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    // Mengambil daftar kontak dengan siapa pengguna pernah mengirim atau menerima pesan
    public function getChatContacts()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Ambil semua kontak yang pernah berinteraksi dengan pengguna
        $contacts = ChatMessage::where('sender_id', Auth::id())
            ->orWhere('receiver_id', Auth::id())
            ->with(['sender', 'receiver'])
            ->get()
            ->map(function ($message) {
                $contact = $message->sender_id === Auth::id() ? $message->receiver : $message->sender;

                return [
                    'user_id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'divisi' => $contact->divisi,
                    'kelas' => $contact->kelas,
                    'last_message' => $message->message_text,
                    'last_online' => Carbon::parse($contact->last_online)->diffForHumans(),
                ];
            })->unique('user_id')->values();

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

        ChatMessage::where('receiver_id', Auth::id())
            ->where('sender_id', $validatedData['receiver_id'])
            ->update(['is_read' => true]);

        return response()->json(['status' => 'Messages marked as read']);
    }
}
