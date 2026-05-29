<?php

namespace App\Application\Identity\Actions;

use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    /**
     * Attempt login. Returns 'ok' on success, '2fa' if 2FA challenge is required, false on failure.
     */
    public function execute(string $email, string $password, bool $remember = false): string|false
    {
        if (!Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            return false;
        }

        $user = Auth::user();

        if ($user->two_factor_enabled && $user->two_factor_secret) {
            // Store user ID for the 2FA challenge step, then log out temporarily
            session(['2fa_user_id' => $user->id, '2fa_remember' => $remember]);
            Auth::logout();
            return '2fa';
        }

        $user->update([
            'last_login_at' => now(),
            'last_active_at' => now(),
        ]);

        setPermissionsTeamId($user->agency_id);

        return 'ok';
    }
}
