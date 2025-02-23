<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function update(Request $request, $id)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'nullable|string|max:255',
            'kelas' => 'nullable|string|max:255',
            'divisi' => 'nullable|string|max:255',
        ]);
    
        try {
            $user = User::findOrFail($id);
    
            // Update image if provided
            if ($request->hasFile('image')) {
                if ($user->img) {
                    Storage::delete($user->img);
                }
                $user->img = $request->file('image')->store('user_images/' . $user->id);
            }
    
            // Update other fields
            $user->update($request->only(['name', 'kelas', 'divisi']));
    
            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
