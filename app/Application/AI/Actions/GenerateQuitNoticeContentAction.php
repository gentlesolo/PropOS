<?php

namespace App\Application\AI\Actions;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Lease;

class GenerateQuitNoticeContentAction
{
    public function __construct(private AiCompletionServiceInterface $ai) {}

    public function execute(Lease $lease, string $reason, string $vacateByDate): string
    {
        $tenant   = $lease->tenant;
        $contact  = $tenant?->contact;
        $property = $lease->listing?->property;
        $agency   = $lease->agency ?? auth()->user()?->agency;

        $tenantName      = $contact?->full_name ?? 'Tenant';
        $propertyAddress = $property
            ? trim("{$property->address_line_1}, {$property->city}")
            : 'the leased premises';
        $agencyName      = $agency?->name ?? 'the Landlord/Agent';
        $leaseRef        = $lease->reference;
        $issueDate       = now()->format('d F Y');
        $vacateFormatted = \Carbon\Carbon::parse($vacateByDate)->format('d F Y');
        $monthlyRent     = number_format((float) $lease->monthly_rent, 2);

        $systemPrompt = <<<'SYSTEM'
You are a property management legal assistant specialising in South African residential and commercial tenancy law.
Draft a formal, professional quit notice letter. The tone must be firm but respectful — legally precise without being unnecessarily aggressive.
Structure the letter with: opening salutation, reference to the lease, stated grounds/reason for the notice, the required vacate date, obligations upon vacating (property condition, key return, meter readings), and a closing paragraph.
Do NOT include a subject line or date header — those are handled separately.
Return ONLY the letter body paragraphs, starting with "Dear [Tenant Name],".
SYSTEM;

        $userPrompt = <<<PROMPT
Draft a quit notice with the following details:

Tenant Name: {$tenantName}
Property Address: {$propertyAddress}
Lease Reference: {$leaseRef}
Agency / Landlord: {$agencyName}
Issue Date: {$issueDate}
Vacate By: {$vacateFormatted}
Monthly Rent: R{$monthlyRent}
Reason for Notice: {$reason}

Write a complete, professional quit notice letter body.
PROMPT;

        return $this->ai->generate($systemPrompt, $userPrompt, [
            'feature'     => 'quit_notice_generation',
            'temperature' => 0.4,
        ]);
    }
}
