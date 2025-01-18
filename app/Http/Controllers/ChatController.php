<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    { 
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Create a new message with sender and receiver IDs
        $chat = ChatMessage::create([
            'sender_id' => Auth::id(), // ID of the authenticated user
            'receiver_id' => $request->receiver_id, // ID of the message recipient
            'message_text' => $request->message_text,
        ]);
    
        // Broadcast event
        event(new MessageSent($chat));
    
        return response()->json(['status' => 'Message Sent!', 'message' => $chat], 201);
    }

    public function getMessages($receiverId)
{
    // Ambil semua pesan dengan relasi sender dan receiver berdasarkan receiver_id
    $messages = ChatMessage::where('receiver_id', $receiverId)
        ->with(['sender', 'receiver']) // Eager loading untuk relasi
        ->get();

    // Kelompokkan pesan berdasarkan sender_id
    $groupedMessages = $messages->groupBy('sender_id')->map(function ($group, $senderId) {
        // Ambil nama sender dari relasi
        $senderName = $group->first()->sender->name ?? 'Unknown';

        return [
            'sender_id' => $senderId,
            'sender_name' => $senderName,
            'messages' => $group->map(function ($message) {
                return [
                    'receiver_id' => $message->receiver->id ?? null,
                    'receiver_name' => $message->receiver->name ?? 'Unknown',
                    'message_text' => $message->message_text,
                    'time' => $message->created_at->format('H:i'),
                    'day' => $message->created_at->format('D'),
                ];
            })->values()
        ];
    })->values();

    // Mengembalikan data sebagai JSON
    return response()->json($groupedMessages);
}


    public function getAllMessage()
{
    // Ambil semua pesan dengan relasi sender dan receiver menggunakan eager loading
    $messages = ChatMessage::with(['sender', 'receiver'])->get();

    // Kelompokkan pesan berdasarkan sender_id
    $groupedMessages = $messages->groupBy('sender_id')->map(function ($group, $senderId) {
        // Ambil nama sender dari relasi
        $senderName = $group->first()->sender->name ?? 'Unknown';

        return [
            'sender_id' => $senderId,
            'sender_name' => $senderName,
            'messages' => $group->map(function ($message) {
                return [
                    'receiver_id' => $message->receiver->id ?? null,
                    'receiver_name' => $message->receiver->name ?? 'Unknown',
                    'message_text' => $message->message_text,
                    'time' => $message->created_at->format('H:i'),
                    'day' => $message->created_at->format('D'),
                ];
            })->values()
        ];
    })->values();

    // Mengembalikan data sebagai JSON
    return response()->json($groupedMessages);
}

    
    public function getContactInfo()
{
    // Mengambil semua data pengguna
    $users = User::all();

    // Cek apakah ada pengguna
    if ($users->isEmpty()) {
        return response()->json(['error' => 'No users found'], 404);
    }

    // Memetakan setiap pengguna untuk mengambil informasi kontak mereka
    $contactInfo = $users->map(function ($user) {
        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'divisi' => $user->divisi,
            'kelas' => $user->kelas,
            'avatar' => $user->avatar, // Misalnya jika ada kolom avatar
            'status' => $user->status, // Misalnya jika ada kolom status
            'last_online' => Carbon::parse($user->last_online)->diffForHumans(), // Menghitung waktu terakhir online
        ];
    });

    // Mengembalikan data sebagai JSON
    return response()->json($contactInfo);
}


}
