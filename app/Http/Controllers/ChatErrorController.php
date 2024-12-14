<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatErrorController extends Controller
{
    public function ChatError() {
        return response()->json([
            'success' => false,
        ], 209); 
    }
}
