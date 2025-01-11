<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\message;
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
    $messages = ChatMessage::where(function($query) use ($receiverId) {
        $query->where('sender_id', Auth::id())
              ->where('receiver_id', $receiverId);
    })->orWhere(function($query) use ($receiverId) {
        $query->where('sender_id', $receiverId)
              ->where('receiver_id', Auth::id());
    })->get();

    return response()->json($messages);
}
}
