<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\AgentDevice;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required',
            'device_name' => 'required|string|max:100',
            'platform'    => 'required|in:ios,android',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->status === 'suspended') {
            return response()->json(['message' => 'Account suspended.'], 403);
        }

        $token = $user->createToken("mobile:{$request->device_name}")->plainTextToken;

        $user->update(['last_login_at' => now()]);
        setPermissionsTeamId($user->agency_id);

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ]);
    }

    /**
     * Rotate the token: delete the current one and issue a fresh one.
     * The mobile app calls this when it detects the token is near expiry
     * or receives a 401 on a non-login request.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user        = $request->user();
        $currentToken = $user->currentAccessToken();
        $deviceName  = str_replace('mobile:', '', $currentToken->name ?? 'ProposMobile');

        $currentToken->delete();

        $newToken = $user->createToken("mobile:{$deviceName}")->plainTextToken;
        $user->update(['last_active_at' => now()]);

        return response()->json([
            'token' => $newToken,
            'user'  => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->formatUser($request->user()));
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $request->validate([
            'platform'    => 'required|in:ios,android',
            'push_token'  => 'required|string',
            'push_type'   => 'required|in:fcm,apns,voip',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        AgentDevice::updateOrCreate(
            ['user_id' => $user->id, 'push_token' => $request->push_token],
            [
                'agency_id'   => $user->agency_id,
                'platform'    => $request->platform,
                'push_type'   => $request->push_type,
                'device_name' => $request->device_name,
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['message' => 'Device registered.']);
    }

    private function formatUser(User $user): array
    {
        return $user->only([
            'id', 'first_name', 'last_name', 'email', 'phone',
            'job_title', 'agency_id', 'avatar_path',
        ]);
    }
}
