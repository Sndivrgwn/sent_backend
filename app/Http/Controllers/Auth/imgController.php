<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class imgController extends Controller
{
    public function updateImg(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|url', // Hanya menerima URL
        ]);

        $item = User::find($id);

        if (!$item) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        $item->image = $request->image;
        $item->save();

        return response()->json([
            'message' => 'Image URL updated successfully',
            'image' => $item->image
        ], 200);
    }

}
