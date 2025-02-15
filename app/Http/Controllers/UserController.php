<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'nama' => 'nullable|string|max:255',
            'divisi' => 'nullable|string|max:255',
            'kelas' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::delete($user->image);
            }
            $validatedData['image'] = $request->file('image')->store('user_images');
        }

        $user->update(array_filter($validatedData));

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }
}
