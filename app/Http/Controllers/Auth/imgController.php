<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class imgController extends Controller
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


}
