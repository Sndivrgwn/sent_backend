<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function message(Request $request) {
        event(new MessageSent($request->input(key: 'username'), $request->input(key: 'messages')));
        
        return response()->json([
            'success' => true,
        ], 200); 
    }
}
