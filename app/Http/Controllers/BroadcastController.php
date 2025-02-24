<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

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
        try {
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
    
            $recipientIds = $broadcast->recipient_ids;
    
            if (!is_array($recipientIds)) {
                return response()->json(['error' => 'Invalid recipient_ids format'], 400);
            }
    
            $invalidRecipients = array_diff($recipientIds, User::pluck('id')->toArray());
            if (!empty($invalidRecipients)) {
                return response()->json(['error' => 'Invalid recipient IDs: ' . implode(', ', $invalidRecipients)], 400);
            }
    
            $messages = [];
            foreach ($recipientIds as $userId) {
                $messages[] = [
                    'sender_id' => Auth::id(),
                    'receiver_id' => $userId,
                    'broadcast_id' => $broadcast->id, // Add this line
                    'message_text' => $validatedData['message_text'],
                    'is_broadcast' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
    
            ChatMessage::insert($messages);
    
            // Trigger Pusher event
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => env('PUSHER_APP_CLUSTER'),
                    'useTLS' => true,
                ]
            );
    
            foreach ($messages as $message) {
                $pusher->trigger('broadcast-chat-channel', 'broadcast-message-sent', $message);
            }
    
            return response()->json([
                'message' => 'Broadcast message sent successfully!',
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending broadcast message: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
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

    public function getBroadcastMessages(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $validated = $request->validate([
        'broadcast_id' => 'required|exists:broadcasts,id',
    ]);

    $broadcastId = $validated['broadcast_id'];

    // Ambil pesan berdasarkan broadcast_id langsung
    $messages = ChatMessage::where('is_broadcast', true)
        ->where('broadcast_id', $broadcastId)
        ->with('sender:id,name,email')
        ->orderBy('created_at', 'desc');

    return response()->json($messages);
}

}
