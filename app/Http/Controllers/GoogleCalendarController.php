<?php

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Models\IntegrationCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    public function redirect(Request $request)
    {
        $client = $this->buildClient();
        $client->addScope(\Google\Service\Calendar::CALENDAR_EVENTS);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $state = csrf_token();
        $client->setState($state);

        session(['google_oauth_state' => $state]);

        return redirect($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        if ($request->input('state') !== session('google_oauth_state')) {
            return redirect()->route('settings')->with('error', 'Invalid OAuth state. Please try again.');
        }

        $code = $request->input('code');
        if (! $code) {
            return redirect()->route('settings')->with('error', 'Google authorisation was denied.');
        }

        try {
            $client = $this->buildClient();
            $token  = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                Log::error('Google Calendar OAuth token error', $token);
                return redirect()->route('settings')->with('error', 'Could not obtain Google token: ' . ($token['error_description'] ?? $token['error']));
            }

            IntegrationCredential::updateOrCreate(
                ['user_id' => auth()->id(), 'provider' => 'google_calendar'],
                [
                    'agency_id'   => auth()->user()->agency_id,
                    'credentials' => json_encode($token),
                    'expires_at'  => isset($token['expires_in'])
                        ? now()->addSeconds($token['expires_in'])
                        : null,
                ]
            );

            session()->forget('google_oauth_state');

            return redirect()->route('settings')->with('success', 'Google Calendar connected successfully.');
        } catch (\Throwable $e) {
            Log::error('Google Calendar OAuth callback failed', ['error' => $e->getMessage()]);
            return redirect()->route('settings')->with('error', 'Google Calendar connection failed. Please try again.');
        }
    }

    public function disconnect()
    {
        IntegrationCredential::where('user_id', auth()->id())
            ->where('provider', 'google_calendar')
            ->delete();

        return redirect()->route('settings')->with('success', 'Google Calendar disconnected.');
    }

    private function buildClient(): \Google\Client
    {
        $client = new \Google\Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(route('google-calendar.callback'));
        return $client;
    }
}
