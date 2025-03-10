<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
                    'role' => $user->role,
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



    public function updateRole(Request $request, $id)
    {
        // Hanya admin yang bisa mengubah role user
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'role' => 'required|string|in:admin,mentor,user', // Sesuaikan dengan daftar role yang tersedia
        ]);

        try {
            $user = User::findOrFail($id);
            $user->role = $request->role;
            $user->save();

            return response()->json([
                'message' => 'User role updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'divisi' => $user->divisi,
                    'kelas' => $user->kelas,
                    'img' => asset('storage/' . $user->img), // Return full image URL
                    'role' => $user->role,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating user role:', [$e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while updating the user role.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        // Hanya admin yang bisa menghapus user
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        try {
            $user = User::findOrFail($id);

            // Hapus gambar jika ada
            if ($user->img) {
                Storage::disk('public')->delete($user->img);
            }

            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting user:', [$e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while deleting the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
