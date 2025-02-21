<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
        public function sendBroadcastMessage(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'message_text' => 'required|string|max:1000',
        ]);

        $senderId = Auth::id();
        $users = User::where('id', '!=', $senderId)->pluck('id'); // Ambil ID user selain pengirim

        $messages = [];

        foreach ($users as $userId) {
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
