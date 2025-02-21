<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    // Menyimpan daftar penerima broadcast
    public function createBroadcast(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'recipient_ids' => 'required|array', // Harus berupa array
            'recipient_ids.*' => 'exists:users,id', // Setiap ID harus valid
        ]);

        $recipientIds = array_unique($validatedData['recipient_ids']);
        session(['broadcast_recipients' => $recipientIds]); // Simpan daftar penerima di sesi

        return response()->json(['message' => 'Broadcast recipients saved.', 'recipients' => $recipientIds]);
    }

    // Menampilkan daftar broadcast yang telah dibuat
    public function getCreatedBroadcasts()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $senderId = Auth::id();
        $broadcasts = ChatMessage::where('sender_id', $senderId)
            ->where('is_broadcast', true)
            ->with('receiver:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($broadcasts);
    }

    // Mengirim pesan broadcast ke daftar penerima yang telah dipilih
    public function sendBroadcastMessage(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'message_text' => 'required|string|max:1000',
        ]);

        $senderId = Auth::id();
        $recipientIds = session('broadcast_recipients', []); // Ambil daftar penerima dari sesi

        if (empty($recipientIds)) {
            return response()->json(['error' => 'No recipients selected'], 400);
        }

        $messages = [];
        foreach ($recipientIds as $userId) {
            $messages[] = [
                'sender_id' => $senderId,
                'receiver_id' => $userId,
                'message_text' => $validatedData['message_text'],
                'is_broadcast' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ChatMessage::insert($messages); // Insert batch untuk efisiensi

        return response()->json(['message' => 'Broadcast message sent successfully!']);
    }

    // Menampilkan pesan yang diterima dalam broadcast
    public function getBroadcastMessages()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $messages = ChatMessage::where('receiver_id', Auth::id())
            ->where('is_broadcast', true)
            ->with('sender:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messages);
    }
}
