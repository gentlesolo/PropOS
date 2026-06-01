<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Tenancy\TenantResolver;
use App\Application\CRM\Actions\DetectDuplicateContactsAction;
use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\ScoreLeadAction;
use Illuminate\Http\Request;

class LeadWebhookController extends Controller
{
    public function receive(
        Request $request, 
        string $agencySlug, 
        string $source,
        DetectDuplicateContactsAction $detector,
        LogContactActivityAction $logAction,
        ScoreLeadAction $scorer
    ) {
        $agency = Agency::where('slug', $agencySlug)->firstOrFail();
        app(TenantResolver::class)->setCurrentAgency($agency);

        $payload = $request->all();

        // 1. Parse lead details based on source
        $leadData = $this->parsePayload($payload, $source);

        if (empty($leadData['first_name']) || empty($leadData['last_name'])) {
            return response()->json(['error' => 'Invalid lead details. First and last name are required.'], 400);
        }

        // 2. Check for duplicates
        $email = $leadData['email'] ?? null;
        $phone = $leadData['phone'] ?? null;
        $duplicates = $detector->execute($email, $phone);

        if ($duplicates->isNotEmpty()) {
            // Duplicate found: Prevent double entry and merge/update existing contact
            $contact = $duplicates->first();
            
            // Merge preferences
            $prefs = $contact->preferences ?? [];
            if (!empty($leadData['preferences'])) {
                $prefs = array_merge($prefs, $leadData['preferences']);
            }
            
            // Merge tags
            $tags = $contact->tags ?? [];
            if (!empty($leadData['tags'])) {
                $tags = array_unique(array_merge($tags, $leadData['tags']));
            }

            // Update contact fields if empty in the original record
            $updates = [];
            if (!$contact->email && $email) $updates['email'] = $email;
            if (!$contact->phone && $phone) $updates['phone'] = $phone;
            if (!$contact->company && !empty($leadData['company'])) $updates['company'] = $leadData['company'];
            
            $updates['preferences'] = $prefs;
            $updates['tags'] = $tags;
            $contact->update($updates);

            // Log activity to the timeline
            $logAction->execute(
                $contact, 
                'system', 
                'Lead Update Captured', 
                "Additional lead inquiry captured from external source [{$source}]. Message: " . ($leadData['notes'] ?? 'N/A')
            );
        } else {
            // No duplicate: Create new contact
            $contact = Contact::create([
                'agency_id' => $agency->id,
                'first_name' => $leadData['first_name'],
                'last_name' => $leadData['last_name'],
                'email' => $email,
                'phone' => $phone,
                'company' => $leadData['company'] ?? null,
                'type' => $leadData['type'] ?? 'buyer',
                'source' => $source,
                'source_detail' => $leadData['source_detail'] ?? null,
                'status' => 'new',
                'preferences' => $leadData['preferences'] ?? [],
                'tags' => $leadData['tags'] ?? [],
                'notes' => $leadData['notes'] ?? null,
            ]);

            // Log activity
            $logAction->execute(
                $contact, 
                'system', 
                'Lead Captured', 
                "Smart lead capture auto-imported from [{$source}]."
            );
        }

        // 3. AI lead scoring & grading
        $scorer->execute($contact);

        return response()->json([
            'success' => true,
            'message' => 'Lead captured and processed successfully.',
            'contact_id' => $contact->id,
            'intent_score' => $contact->intent_score,
        ], 200);
    }

    private function parsePayload(array $payload, string $source): array
    {
        $data = [
            'first_name' => '',
            'last_name' => '',
            'email' => null,
            'phone' => null,
            'company' => null,
            'type' => 'buyer',
            'notes' => '',
            'preferences' => [],
            'tags' => [],
        ];

        switch (strtolower($source)) {
            case 'zillow':
                // Zillow Tech Connect format parsing
                $contact = $payload['Contact'] ?? $payload;
                $data['first_name'] = $contact['FirstName'] ?? $contact['first_name'] ?? '';
                $data['last_name'] = $contact['LastName'] ?? $contact['last_name'] ?? '';
                $data['email'] = $contact['Email'] ?? $contact['email'] ?? null;
                $data['phone'] = $contact['Phone'] ?? $contact['phone'] ?? null;
                $data['notes'] = $payload['Message'] ?? $payload['notes'] ?? '';
                $data['tags'] = ['zillow_lead', 'portal'];

                // Preferences
                if (isset($payload['Property'])) {
                    $prop = $payload['Property'];
                    $data['preferences'] = [
                        'max_budget' => $prop['Price'] ?? null,
                        'min_bedrooms' => $prop['Bedrooms'] ?? null,
                        'areas' => isset($prop['City']) ? [$prop['City']] : [],
                    ];
                }
                break;

            case 'realtor':
                // Realtor.com Lead API format parsing
                $lead = $payload['lead'] ?? $payload;
                $customer = $lead['customer'] ?? [];
                $data['first_name'] = $customer['first_name'] ?? '';
                $data['last_name'] = $customer['last_name'] ?? '';
                $data['email'] = $customer['email'] ?? null;
                $data['phone'] = $customer['phone'] ?? null;
                $data['notes'] = $lead['inquiry']['message'] ?? '';
                $data['tags'] = ['realtor_lead', 'portal'];

                // Preferences
                if (isset($lead['inquiry']['property'])) {
                    $prop = $lead['inquiry']['property'];
                    $data['preferences'] = [
                        'max_budget' => $prop['price'] ?? null,
                        'min_bedrooms' => $prop['beds'] ?? null,
                        'property_types' => isset($prop['type']) ? [$prop['type']] : [],
                    ];
                }
                break;

            case 'facebook':
            case 'social':
                // Facebook Lead Ads webhook format parsing
                $data['first_name'] = $payload['first_name'] ?? '';
                $data['last_name'] = $payload['last_name'] ?? '';
                $data['email'] = $payload['email'] ?? null;
                $data['phone'] = $payload['phone'] ?? null;
                $data['notes'] = $payload['campaign_name'] ?? 'Social Lead Ad';
                $data['tags'] = ['social_ad', 'facebook_campaign'];
                break;

            default:
                // Websites / Custom landing pages format
                $data['first_name'] = $payload['first_name'] ?? '';
                $data['last_name'] = $payload['last_name'] ?? '';
                $data['email'] = $payload['email'] ?? null;
                $data['phone'] = $payload['phone'] ?? null;
                $data['company'] = $payload['company'] ?? null;
                $data['type'] = $payload['type'] ?? 'buyer';
                $data['notes'] = $payload['message'] ?? $payload['notes'] ?? '';
                $data['tags'] = $payload['tags'] ?? ['website_lead'];
                if (isset($payload['preferences'])) {
                    $data['preferences'] = $payload['preferences'];
                }
                break;
        }

        return $data;
    }
}
