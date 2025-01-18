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

    public function getMessages($userId)
{
    $messages = ChatMessage::where(function($query) use ($userId) {
        $query->where('sender_id', Auth::id())
              ->where('receiver_id', $userId);
    })->orWhere(function($query) use ($userId) {
        $query->where('sender_id', $userId)
              ->where('receiver_id', Auth::id());
    })->get();

    return response()->json($messages);
}

    public function getAllMessage() {
         // Mengambil semua pesan dari tabel messages
         $messages = ChatMessage::all();

         // Mengelompokkan pesan berdasarkan sender_id dan mengambil nama sender serta receiver
         $groupedMessages = $messages->groupBy('sender_id')->map(function ($group, $senderId) {
             // Mengambil nama pengirim (sender) berdasarkan sender_id
             $sender = User::find($senderId); // Mengambil data user berdasarkan sender_id
             $senderName = $sender ? $sender->name : 'Unknown'; // Nama sender
     
             return [
                 'sender_id' => $senderId,
                 'sender_name' => $senderName,
                 'messages' => $group->map(function ($message) {
                     // Mengambil nama penerima (receiver) berdasarkan receiver_id
                     $receiver = User::find($message->receiver_id); // Mengambil data user berdasarkan receiver_id
                     $receiverName = $receiver ? $receiver->name : 'Unknown'; // Nama receiver
     
                     return [
                         'receiver_id' => $receiver->id,
                         'receiver_name' => $receiverName,
                         'message_text' => $message->message_text,
                         'time' => Carbon::parse($message->created_at)->format('H:i'),
                         'day' => Carbon::parse($message->created_at)->format('D')
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
