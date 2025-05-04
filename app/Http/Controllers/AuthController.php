<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username'      => 'required',
            'no_hp'  => 'required',
            'password'      => 'required|confirmed',
        ]);

        $user = User::create([
            'username'      => $request->username,
            'no_hp'  => $request->no_hp,
            'password'      => Hash::make($request->password),
            'deskripsi_user'      => 'Belum mempunyai/menambah info',
            'role'          => 'user',
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['error' => 'Username tidak ditemukan.'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Password salah.'], 401);
        }

        $token = $user->createToken('mobile_auth')->plainTextToken;

        Auth::login($user);
        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Logout berhasil.'], 200);
    }

    public function logoutToken(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Logout berhasil.'], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
}
