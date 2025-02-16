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

    // Jika ada file gambar baru, hapus yang lama dan simpan yang baru
    if ($request->hasFile('image')) {
        if ($user->img) {
            Storage::delete($user->img);
        }
        $user->img = $request->file('image')->store('user_images');
    }

    // Update hanya field yang diberikan dalam request
    if ($request->filled('name')) {
        $user->name = $request->name;
    }
    if ($request->filled('kelas')) {
        $user->kelas = $request->kelas;
    }
    if ($request->filled('divisi')) {
        $user->divisi = $request->divisi;
    }

    $user->save(); // Simpan perubahan

    return response()->json([
        'message' => 'User updated successfully',
        'user' => $user
    ]);
}

}
