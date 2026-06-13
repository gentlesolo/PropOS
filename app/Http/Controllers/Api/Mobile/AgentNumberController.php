<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\AgentNumber;
use App\Infrastructure\Services\TwilioVoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentNumberController extends Controller
{
    public function __construct(private readonly TwilioVoiceService $twilio) {}

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
     * Register a number for the agent.
     *
     * type=twilio_provisioned  → platform buys a Twilio number in the given country
     * type=verified_caller_id  → Twilio calls the agent's real number to verify it
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type'         => 'required|in:twilio_provisioned,verified_caller_id',
            'country_code' => 'required|string|size:2',
            'number'       => 'required_if:type,verified_caller_id|nullable|string|max:20',
            'area_code'    => 'nullable|string|max:10',
        ]);

        $user = $request->user();

        if ($request->type === 'twilio_provisioned') {
            $agentNumber = $this->twilio->provisionNumber(
                $user,
                strtoupper($request->country_code),
                $request->area_code ?? '',
            );

            return response()->json($agentNumber, 201);
        }

        // BYON — verified_caller_id path
        $phoneNumber = $request->string('number')->toString();

        // Prevent duplicate registrations
        $existing = AgentNumber::where('user_id', $user->id)
            ->where('display_number', $phoneNumber)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'This number is already registered on your account.',
                'number'  => $existing,
            ], 409);
        }

        $verification = $this->twilio->initiateCallerIdVerification($phoneNumber);

        $agentNumber = AgentNumber::create([
            'agency_id'      => $user->agency_id,
            'user_id'        => $user->id,
            'twilio_number'  => null,
            'display_number' => $phoneNumber,
            'number_type'    => 'verified_caller_id',
            'country_code'   => strtoupper($request->country_code),
            'verified'       => false,
            'active'         => false,
        ]);

        return response()->json([
            'id'              => $agentNumber->id,
            'display_number'  => $agentNumber->display_number,
            'number_type'     => $agentNumber->number_type,
            'country_code'    => $agentNumber->country_code,
            'verified'        => false,
            'active'          => false,
            'validation_code' => $verification['validation_code'],
            'message'         => "We're calling {$phoneNumber}. When prompted, enter the code on your keypad.",
        ], 201);
    }

    /**
     * Poll whether Twilio has confirmed a BYON number.
     * Mobile calls this every few seconds after initiating verification.
     */
    public function checkVerification(Request $request, AgentNumber $agentNumber): JsonResponse
    {
        abort_unless($agentNumber->user_id === $request->user()->id, 403);
        abort_unless($agentNumber->number_type === 'verified_caller_id', 400, 'Only applicable to BYON numbers.');

        if ($agentNumber->verified) {
            return response()->json(['verified' => true, 'number' => $agentNumber]);
        }

        $callerIdSid = $this->twilio->checkCallerIdVerified($agentNumber->display_number);

        if ($callerIdSid) {
            // Deactivate other numbers and activate this one
            AgentNumber::where('user_id', $request->user()->id)
                ->where('id', '!=', $agentNumber->id)
                ->update(['active' => false]);

            $agentNumber->update([
                'verified'      => true,
                'verified_at'   => now(),
                'caller_id_sid' => $callerIdSid,
                'active'        => true,
            ]);
        }

        return response()->json(['verified' => (bool) $callerIdSid, 'number' => $agentNumber->fresh()]);
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
     * Releases Twilio-provisioned numbers back to Twilio and removes verified caller IDs.
     */
    public function destroy(Request $request, AgentNumber $agentNumber): JsonResponse
    {
        abort_unless($agentNumber->user_id === $request->user()->id, 403);

        if ($agentNumber->number_type === 'twilio_provisioned' && $agentNumber->twilio_sid) {
            try {
                $this->twilio->releaseNumber($agentNumber->twilio_sid);
            } catch (\Throwable $e) {
                Log::warning("Failed to release Twilio number {$agentNumber->twilio_sid}: {$e->getMessage()}");
            }
        }

        if ($agentNumber->number_type === 'verified_caller_id' && $agentNumber->caller_id_sid) {
            try {
                $this->twilio->removeCallerId($agentNumber->caller_id_sid);
            } catch (\Throwable $e) {
                Log::warning("Failed to remove Twilio caller ID {$agentNumber->caller_id_sid}: {$e->getMessage()}");
            }
        }

        $agentNumber->delete();

        return response()->json(null, 204);
    }
}
