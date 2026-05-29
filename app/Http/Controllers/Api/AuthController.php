<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->status === 'suspended') {
            return response()->json(['message' => 'Account suspended.'], 403);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        $user->update(['last_login_at' => now()]);
        setPermissionsTeamId($user->agency_id);

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'first_name', 'last_name', 'email', 'phone', 'job_title', 'agency_id', 'avatar_path']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('agency'));
    }
}
