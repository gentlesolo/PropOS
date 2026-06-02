<?php

namespace App\Http\Controllers\Api\Public;

use App\Application\CRM\Actions\DetectDuplicateContactsAction;
use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\ScoreLeadAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Tenancy\TenantResolver;
use App\Infrastructure\Persistence\Models\Agency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicLeadController extends Controller
{
    public function store(
        Request $request,
        DetectDuplicateContactsAction $detector,
        LogContactActivityAction $logger,
        ScoreLeadAction $scorer,
    ): JsonResponse {
        $validated = $request->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:30',
            'message'     => 'nullable|string|max:2000',
            'listing_id'  => 'nullable|integer',
            'type'        => 'nullable|in:buyer,seller,landlord,tenant,investor',
            'preferences' => 'nullable|array',
            'tags'        => 'nullable|array',
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json(['error' => 'Email or phone is required.'], 422);
        }

        $agencyId = $request->attributes->get('agency_id');
        $agency   = Agency::findOrFail($agencyId);

        app(TenantResolver::class)->setCurrentAgency($agency);

        $duplicates = $detector->execute($validated['email'] ?? null, $validated['phone'] ?? null);

        if ($duplicates->isNotEmpty()) {
            $contact = $duplicates->first();
            $contact->update([
                'preferences' => array_merge($contact->preferences ?? [], $validated['preferences'] ?? []),
                'tags'        => array_unique(array_merge($contact->tags ?? [], $validated['tags'] ?? ['website_lead'])),
            ]);
            $logger->execute($contact, 'system', 'Website Inquiry', $validated['message'] ?? 'Re-inquiry via website widget.');
        } else {
            $contact = Contact::create([
                'agency_id'  => $agencyId,
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'] ?? null,
                'phone'      => $validated['phone'] ?? null,
                'type'       => $validated['type'] ?? 'buyer',
                'source'     => 'website',
                'source_detail' => $validated['listing_id'] ? "Listing #{$validated['listing_id']}" : null,
                'status'     => 'new',
                'notes'      => $validated['message'] ?? null,
                'preferences'=> $validated['preferences'] ?? [],
                'tags'       => array_unique(array_merge($validated['tags'] ?? [], ['website_lead'])),
            ]);
            $logger->execute($contact, 'system', 'Lead Captured', 'Submitted via website widget.');
        }

        $scorer->execute($contact);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! An agent will be in touch shortly.',
        ], 201);
    }
}
