<?php

namespace App\Application\Identity\Actions;

use App\Application\Identity\DTOs\RegisterUserData;
use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    /**
     * Register a new user and their agency, making them the Principal.
     */
    public function execute(RegisterUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $aiCredits = config("pricing.plans.{$data->subscriptionPlan}.ai_credits_monthly", 200);

            // 1. Create the Agency
            $agency = Agency::create([
                'name' => $data->agencyName,
                'slug' => $data->slug,
                'email' => $data->email,
                'country_code' => $data->country,
                'settings' => ['size' => $data->size],
                'subscription_plan' => $data->subscriptionPlan,
                'billing_cycle' => $data->billingCycle,
                'subscription_status' => 'trialing',
                'ai_credits_balance' => $aiCredits,
                'ai_credits_allocated_monthly' => $aiCredits,
            ]);

            // Set Spatie Permission team context to target this agency
            setPermissionsTeamId($agency->id);

            // 2. Create the Principal User
            $user = User::create([
                'agency_id' => $agency->id,
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'email' => $data->email,
                'phone' => $data->phone,
                'job_title' => $data->role,
                'password' => Hash::make($data->password),
                'status' => 'active',
                'email_verified_at' => now(), // Auto-verify for simplicity initially
            ]);

            // 3. Assign Principal Role to the User
            $user->assignRole('principal');

            return $user;
        });
    }
}
