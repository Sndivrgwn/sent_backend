<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    public function createBroadcast(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'exists:users,id',
        ]);

        $broadcast = Broadcast::create([
            'sender_id' => Auth::id(),
            'recipient_ids' => array_unique($validatedData['recipient_ids']),
        ]);

        return response()->json(['message' => 'Broadcast created successfully!', 'broadcast' => $broadcast]);
    }

    public function sendBroadcastMessage(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validatedData = $request->validate([
            'broadcast_id' => 'required|exists:broadcasts,id',
            'message_text' => 'required|string|max:1000',
        ]);

        $broadcast = Broadcast::where('id', $validatedData['broadcast_id'])
            ->where('sender_id', Auth::id())
            ->first();

        if (!$broadcast) {
            return response()->json(['error' => 'Broadcast not found or unauthorized'], 403);
        }

        $messages = [];
        foreach ($broadcast->recipient_ids as $userId) {
            $messages[] = [
                'sender_id' => Auth::id(),
                'receiver_id' => $userId,
                'message_text' => $validatedData['message_text'],
                'is_broadcast' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ChatMessage::insert($messages);

        return response()->json(['message' => 'Broadcast message sent successfully!', $messages]);
    }

    public function getCreatedBroadcasts()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $broadcasts = Broadcast::where('sender_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($broadcasts);
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
