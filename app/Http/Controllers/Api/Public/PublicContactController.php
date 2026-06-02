<?php

namespace App\Http\Controllers\Api\Public;

use App\Application\CRM\Actions\DetectDuplicateContactsAction;
use App\Application\CRM\Actions\LogContactActivityAction;
use App\Application\CRM\Actions\ScoreLeadAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Tenancy\TenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicContactController extends Controller
{
    // ── List / Search ────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->attributes->get('agency_id');

        $contacts = Contact::withoutGlobalScopes()
            ->where('agency_id', $agencyId)
            ->when($request->email,  fn($q) => $q->where('email', $request->email))
            ->when($request->phone,  fn($q) => $q->where('phone', $request->phone))
            ->when($request->type,   fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->tag,    fn($q) => $q->whereJsonContains('tags', $request->tag))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('first_name', 'like', "%{$request->search}%")
                       ->orWhere('last_name',  'like', "%{$request->search}%")
                       ->orWhere('email',      'like', "%{$request->search}%")
                       ->orWhere('phone',      'like', "%{$request->search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($contacts->through(fn($c) => $this->formatContact($c)));
    }

    // ── Single contact ───────────────────────────────────────────────────────

    public function show(Request $request, int $id): JsonResponse
    {
        $contact = $this->findContact($id, $request->attributes->get('agency_id'));

        return response()->json($this->formatContact($contact, detailed: true));
    }

    // ── Update contact fields ────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $agencyId = $request->attributes->get('agency_id');
        $contact  = $this->findContact($id, $agencyId);

        $validated = $request->validate([
            'first_name'         => 'sometimes|string|max:100',
            'last_name'          => 'sometimes|string|max:100',
            'email'              => 'sometimes|nullable|email|max:255',
            'phone'              => 'sometimes|nullable|string|max:30',
            'secondary_phone'    => 'sometimes|nullable|string|max:30',
            'company'            => 'sometimes|nullable|string|max:255',
            'job_title'          => 'sometimes|nullable|string|max:255',
            'type'               => 'sometimes|in:buyer,seller,landlord,tenant,investor,referral_partner',
            'status'             => 'sometimes|in:new,active,qualified,nurturing,closed,archived',
            'notes'              => 'sometimes|nullable|string|max:5000',
            'preferences'        => 'sometimes|array',
            'assigned_agent_id'  => 'sometimes|nullable|integer|exists:users,id',
        ]);

        $contact->update($validated);

        return response()->json($this->formatContact($contact->fresh()));
    }

    // ── Add tags ─────────────────────────────────────────────────────────────

    public function addTags(Request $request, int $id): JsonResponse
    {
        $contact = $this->findContact($id, $request->attributes->get('agency_id'));

        $validated = $request->validate([
            'tags'   => 'required|array|min:1|max:50',
            'tags.*' => 'string|max:80',
        ]);

        $existing = $contact->tags ?? [];
        $merged   = array_values(array_unique(array_merge($existing, $validated['tags'])));

        $contact->update(['tags' => $merged]);

        return response()->json([
            'contact_id' => $contact->id,
            'tags'       => $merged,
        ]);
    }

    // ── Remove tags ──────────────────────────────────────────────────────────

    public function removeTags(Request $request, int $id): JsonResponse
    {
        $contact = $this->findContact($id, $request->attributes->get('agency_id'));

        $validated = $request->validate([
            'tags'   => 'required|array|min:1',
            'tags.*' => 'string|max:80',
        ]);

        $remaining = array_values(array_diff($contact->tags ?? [], $validated['tags']));

        $contact->update(['tags' => $remaining]);

        return response()->json([
            'contact_id' => $contact->id,
            'tags'       => $remaining,
        ]);
    }

    // ── Create contact ───────────────────────────────────────────────────────

    public function store(
        Request $request,
        DetectDuplicateContactsAction $detector,
        LogContactActivityAction $logger,
        ScoreLeadAction $scorer,
    ): JsonResponse {
        $agencyId = $request->attributes->get('agency_id');
        $agency   = Agency::findOrFail($agencyId);
        app(TenantResolver::class)->setCurrentAgency($agency);

        $validated = $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'secondary_phone'   => 'nullable|string|max:30',
            'company'           => 'nullable|string|max:255',
            'job_title'         => 'nullable|string|max:255',
            'type'              => 'nullable|in:buyer,seller,landlord,tenant,investor,referral_partner',
            'status'            => 'nullable|in:new,active,qualified,nurturing,closed,archived',
            'source'            => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:5000',
            'tags'              => 'nullable|array',
            'tags.*'            => 'string|max:80',
            'preferences'       => 'nullable|array',
            'assigned_agent_id' => 'nullable|integer|exists:users,id',
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json(['error' => 'Email or phone is required.'], 422);
        }

        // Deduplicate
        $duplicates = $detector->execute($validated['email'] ?? null, $validated['phone'] ?? null);
        if ($duplicates->isNotEmpty()) {
            return response()->json([
                'error'      => 'A contact with this email or phone already exists.',
                'contact_id' => $duplicates->first()->id,
                'duplicate'  => true,
            ], 409);
        }

        $contact = Contact::create(array_merge($validated, [
            'agency_id' => $agencyId,
            'type'      => $validated['type']   ?? 'buyer',
            'status'    => $validated['status'] ?? 'new',
            'tags'      => array_unique(array_merge($validated['tags'] ?? [], ['api_import'])),
        ]));

        $logger->execute($contact, 'system', 'Contact Created via API', 'Imported via full-access API key.');
        $scorer->execute($contact);

        return response()->json($this->formatContact($contact->fresh()), 201);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function findContact(int $id, int $agencyId): Contact
    {
        return Contact::withoutGlobalScopes()
            ->where('id', $id)
            ->where('agency_id', $agencyId)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function formatContact(Contact $c, bool $detailed = false): array
    {
        $data = [
            'id'           => $c->id,
            'first_name'   => $c->first_name,
            'last_name'    => $c->last_name,
            'email'        => $c->email,
            'phone'        => $c->phone,
            'type'         => $c->type,
            'status'       => $c->status,
            'intent_score' => $c->intent_score,
            'tags'         => $c->tags ?? [],
            'source'       => $c->source,
            'created_at'   => $c->created_at?->toISOString(),
            'updated_at'   => $c->updated_at?->toISOString(),
        ];

        if ($detailed) {
            $data['secondary_phone']   = $c->secondary_phone;
            $data['company']           = $c->company;
            $data['job_title']         = $c->job_title;
            $data['notes']             = $c->notes;
            $data['preferences']       = $c->preferences ?? [];
            $data['source_detail']     = $c->source_detail;
            $data['last_contacted_at'] = $c->last_contacted_at?->toISOString();
            $data['assigned_agent_id'] = $c->assigned_agent_id;
        }

        return $data;
    }
}
