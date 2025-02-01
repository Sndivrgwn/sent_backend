<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class ImageController extends Controller
{
    public function updateImg(Request $request, $id)
    {
        Log::info($request->all()); // Debug request
    
        $request->validate([
            'image' => 'required|url',
        ]);
    
        $item = User::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Data not found'], 404);
        }
    
        $item->img = $request->image;
        $item->save();
    
        return response()->json([
            'message' => 'Image URL updated successfully',
            'image' => $item->img
        ], 200);
    }

    public function updateGroupImg(Request $request, $id)
    {
        Log::info($request->all()); // Debug request
    
        $request->validate([
            'image' => 'required|url',
        ]);
    
        $item = ChatGroup::find($id);
        
        if (!$item) {
            return response()->json(['message' => 'Data not found'], 404);
        }
    
        $item->img = $request->image;
        $item->save();
    
        return response()->json([
            'message' => 'Image URL updated successfully',
            'image' => $item->img
        ], 200);
    }
    
}
