<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\User;
use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class authController extends Controller
{
    public function register(Request $request)
    {
        $validator = FacadesValidator::make($request->all(), [
            'name'      => 'required',
            'email'     => 'required|email|unique:users',
            'kelas'     => 'min:2',
            'divisi'    => 'alpha:ascii',
            'password'  => 'required|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'kelas'     => $request->kelas,
            'divisi'    => $request->divisi,
            'password'  => bcrypt($request->password)
        ]);

        // Cari grup berdasarkan divisi
        $group = ChatGroup::where('name', $request->divisi)->first();

        // if (!$group) {
        //     // Buat grup baru jika belum ada
        //     $group = ChatGroup::create([
        //         'name' => $request->divisi,
        //         'created_by' => $user->id, // Atau ID admin
        //         'img' => 'default_group_image.png', // Path gambar default
        //     ]);
        // }

        if ($group) {
            // Tambahkan user ke grup
            ChatGroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
            ]);
        }

        return response()->json(['message' => 'User registered successfully!', 'user' => $user], 201);
    }

    // Login pengguna
    public function login(Request $request)
    {
        $validator = FacadesValidator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('MyApp')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    // Logout pengguna
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function getUserById($id)
    {
        $user = User::find($id);

        if ($user) {
            $user->img = asset('storage/' . $user->img);

            return response()->json($user);
        } else {
            return response()->json(['error' => 'User  not found'], 404);
        }
    }
}
