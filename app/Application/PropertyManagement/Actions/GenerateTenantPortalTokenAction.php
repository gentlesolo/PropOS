<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GenerateTenantPortalTokenAction
{
    public function execute(Tenant $tenant): string
    {
        $token = Str::uuid()->toString();

        $tenant->update(['portal_token' => $token]);

        $this->sendPortalLink($tenant, $token);

        return $token;
    }

    private function sendPortalLink(Tenant $tenant, string $token): void
    {
        $contact = $tenant->contact;

        if (! $contact?->email) {
            return;
        }

        $url     = url("/tenant-portal/{$token}");
        $address = $tenant->listing?->property
            ? "{$tenant->listing->property->address_line_1}, {$tenant->listing->property->city}"
            : 'your property';

        $body = "Dear {$contact->first_name},\n\n"
            . "Your tenant portal for {$address} is now ready.\n\n"
            . "Access your portal here:\n{$url}\n\n"
            . "Through the portal you can:\n"
            . "  • View your lease details\n"
            . "  • Check your payment history\n"
            . "  • Submit maintenance requests\n"
            . "  • Download your lease agreement\n\n"
            . "This link is unique to you — please do not share it.\n\n"
            . "Kind regards,\nProperty Management";

        try {
            Mail::raw($body, fn ($msg) => $msg->to($contact->email, $contact->full_name)->subject('Your Tenant Portal Is Ready'));
        } catch (\Exception $e) {
            Log::error('Tenant portal link email failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }
}
