<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string'],
            'bio' => ['required', 'string', 'max:100'],
            'username' => ['required', 'min:3', 'unique:users,username', 'regex:/^[a-zA-Z0-9._]+$/'],
            'password' => ['required', 'min:6'],
            'is_private' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid field',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'bio' => $request->bio,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'is_private' => $request->is_private ?? false,
            'created_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Register success',
            'token' => $token,
            'user' => [
                'full_name' => $user->full_name,
                'bio' => $user->bio,
                'username' => $user->username,
                'is_private' => (bool) $user->is_private,
                'id' => $user->id,
            ]
        ]);
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Wrong username or password'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'bio' => $user->bio,
                'is_private' => (int) $user->is_private,
                'created_at' => $user->created_at,
            ]
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Logout success'
        ]);
    }
}
