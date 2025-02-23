<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function update(Request $request, $id)
    {
        $request->validate([
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'nullable|string|max:255',
            'kelas' => 'nullable|string|max:255',
            'divisi' => 'nullable|string|max:255',
        ]);
    
        try {
            $user = User::findOrFail($id);
    
            // Update image if provided
            if ($request->hasFile('img')) {
                Log::info('Image received:', [$request->file('img')]);
    
                if ($user->img) {
                    Log::info('Deleting old image:', [$user->img]);
                    Storage::disk('public')->delete($user->img);
                }
    
                $path = $request->file('img')->store('user_images/' . $user->id, 'public');
                Log::info('New image stored at:', [$path]);
    
                $user->img = $path;
            }
    
            // Update other fields
            $user->update($request->only(['name', 'kelas', 'divisi']));
    
            return response()->json([
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'kelas' => $user->kelas,
                    'divisi' => $user->divisi,
                    'img' => asset('storage/' . $user->img), // Return full image URL
                ],
            ], 200);
    
        } catch (\Exception $e) {
            Log::error('Error updating user:', [$e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while updating the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}