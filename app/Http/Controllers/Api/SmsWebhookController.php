<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Services\SmsService;
use Illuminate\Http\Request;

class SmsWebhookController extends Controller
{
    /**
     * Twilio inbound SMS webhook (POST /api/webhooks/sms/twilio).
     * Twilio signs every request — validate with X-Twilio-Signature.
     */
    public function twilio(Request $request, SmsService $smsService)
    {
        if (! $this->validateTwilioSignature($request)) {
            return response('Forbidden', 403);
        }

        $from = $request->input('From');
        $body = $request->input('Body', '');
        $sid  = $request->input('MessageSid', '');

        if (! $from) {
            return response('Bad Request', 400);
        }

        $contact = Contact::withoutGlobalScopes()
            ->where('phone', 'like', '%' . ltrim(preg_replace('/\D/', '', $from), '0'))
            ->orWhere('phone', $from)
            ->first();

        $smsService->recordInbound($from, $body, $sid, $contact);

        // Return empty TwiML so Twilio doesn't retry
        return response('<Response></Response>', 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Africa's Talking inbound SMS webhook (POST /api/webhooks/sms/africastalking).
     */
    public function africasTalking(Request $request, SmsService $smsService)
    {
        $from = $request->input('from');
        $body = $request->input('text', '');
        $id   = $request->input('id', uniqid('at-'));

        if (! $from) {
            return response()->json(['status' => 'ignored']);
        }

        $contact = Contact::withoutGlobalScopes()
            ->where('phone', 'like', '%' . ltrim(preg_replace('/\D/', '', $from), '0'))
            ->orWhere('phone', $from)
            ->first();

        $smsService->recordInbound($from, $body, $id, $contact);

        return response()->json(['status' => 'ok']);
    }

    private function validateTwilioSignature(Request $request): bool
    {
        $authToken = config('services.twilio.token');

        // Skip validation in local/testing environments
        if (! $authToken || app()->environment('local', 'testing')) {
            return true;
        }

        $signature = $request->header('X-Twilio-Signature', '');
        $url       = $request->fullUrl();
        $params    = $request->isMethod('POST') ? $request->post() : [];

        ksort($params);
        $data = $url . implode('', array_map(fn($k, $v) => $k . $v, array_keys($params), $params));

        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expected, $signature);
    }
}
