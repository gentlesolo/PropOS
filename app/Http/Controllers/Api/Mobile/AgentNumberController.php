<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\AgentNumber;
use App\Infrastructure\Services\LiveKitVoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentNumberController extends Controller
{
    public function __construct(private readonly LiveKitVoiceService $liveKit) {}

    /**
     * List all numbers registered for the authenticated agent.
     */
    public function index(Request $request): JsonResponse
    {
        $numbers = AgentNumber::where('user_id', $request->user()->id)
            ->orderByDesc('active')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($numbers);
    }

    /**
     * Register a number.
     *
     * type=twilio_provisioned  → not supported in LiveKit stack
     * type=verified_caller_id  → sends an SMS OTP to the agent's number to prove ownership
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type'         => 'required|in:verified_caller_id',
            'country_code' => 'required|string|size:2',
            'number'       => 'required|string|max:20',
        ]);

        $user        = $request->user();
        $phoneNumber = $request->string('number')->toString();

        $existing = AgentNumber::where('user_id', $user->id)
            ->where('display_number', $phoneNumber)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'This number is already registered on your account.',
                'number'  => $existing,
            ], 409);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $agentNumber = AgentNumber::create([
            'agency_id'               => $user->agency_id,
            'user_id'                 => $user->id,
            'twilio_number'           => null,
            'display_number'          => $phoneNumber,
            'number_type'             => 'verified_caller_id',
            'country_code'            => strtoupper($request->country_code),
            'verified'                => false,
            'active'                  => false,
            'verification_code'       => $code,
            'verification_expires_at' => now()->addMinutes(10),
        ]);

        $sent = $this->liveKit->sendVerificationOtp($phoneNumber, $code);

        return response()->json([
            'id'             => $agentNumber->id,
            'display_number' => $agentNumber->display_number,
            'number_type'    => $agentNumber->number_type,
            'country_code'   => $agentNumber->country_code,
            'verified'       => false,
            'active'         => false,
            'sms_sent'       => $sent,
            'message'        => $sent
                ? "A 6-digit code has been sent to {$phoneNumber}. Enter it below to verify."
                : "SMS delivery failed — check your AT_API_KEY in .env. Your code is: {$code} (dev only).",
        ], 201);
    }

    /**
     * Submit the OTP code received via SMS to complete verification.
     */
    public function confirm(Request $request, AgentNumber $agentNumber): JsonResponse
    {
        abort_unless($agentNumber->user_id === $request->user()->id, 403);
        abort_unless($agentNumber->number_type === 'verified_caller_id', 400, 'Only BYON numbers need confirmation.');
        abort_unless(! $agentNumber->verified, 422, 'Number is already verified.');

        $request->validate(['code' => 'required|string|size:6']);

        if (
            $agentNumber->verification_code !== $request->code ||
            now()->isAfter($agentNumber->verification_expires_at)
        ) {
            return response()->json(['message' => 'Invalid or expired code. Please request a new one.'], 422);
        }

        // Deactivate other numbers, activate and verify this one
        AgentNumber::where('user_id', $request->user()->id)
            ->where('id', '!=', $agentNumber->id)
            ->update(['active' => false]);

        $agentNumber->update([
            'verified'                => true,
            'verified_at'             => now(),
            'active'                  => true,
            'verification_code'       => null,
            'verification_expires_at' => null,
        ]);

        return response()->json($agentNumber->fresh());
    }

    /**
     * Resend the OTP SMS for a pending verification.
     */
    public function resendOtp(Request $request, AgentNumber $agentNumber): JsonResponse
    {
        abort_unless($agentNumber->user_id === $request->user()->id, 403);
        abort_unless(! $agentNumber->verified, 422, 'Number is already verified.');

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $agentNumber->update([
            'verification_code'       => $code,
            'verification_expires_at' => now()->addMinutes(10),
        ]);

        $sent = $this->liveKit->sendVerificationOtp($agentNumber->display_number, $code);

        return response()->json([
            'message'  => $sent ? 'A new code has been sent.' : 'SMS failed. Check AT_API_KEY.',
            'sms_sent' => $sent,
        ]);
    }

    /**
     * Set a verified number as the active calling number for this agent.
     */
    public function activate(Request $request, AgentNumber $agentNumber): JsonResponse
    {
        abort_unless($agentNumber->user_id === $request->user()->id, 403);
        abort_unless($agentNumber->verified, 422, 'Number is not yet verified.');

        AgentNumber::where('user_id', $request->user()->id)->update(['active' => false]);
        $agentNumber->update(['active' => true]);

        return response()->json($agentNumber->fresh());
    }

    /**
     * Remove a number from the agent's account.
     */
    public function destroy(Request $request, AgentNumber $agentNumber): JsonResponse
    {
        abort_unless($agentNumber->user_id === $request->user()->id, 403);

        $agentNumber->delete();

        return response()->json(null, 204);
    }
}
