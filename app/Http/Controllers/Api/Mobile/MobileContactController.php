<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\ContactActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contacts = Contact::select([
                'id', 'first_name', 'last_name', 'phone', 'email',
                'status', 'avatar_path', 'last_contacted_at', 'assigned_agent_id',
            ])
            ->when($request->search, fn ($q) => $q->where(function ($s) use ($request) {
                $s->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->when($request->mine, fn ($q) => $q->where('assigned_agent_id', $request->user()->id))
            ->latest('last_contacted_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($contacts);
    }

    public function show(Contact $contact): JsonResponse
    {
        $contact->load([
            'assignedAgent:id,first_name,last_name,avatar_path',
            'deals.stage:id,name,color',
        ]);

        $recentCalls = $contact->calls()
            ->with('summary:id,call_id,sentiment,summary_text')
            ->latest('started_at')
            ->limit(5)
            ->get(['id', 'contact_id', 'direction', 'status', 'duration_seconds', 'started_at']);

        return response()->json([
            'contact'      => $contact,
            'recent_calls' => $recentCalls,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'nullable|string|max:50',
            'email'      => 'nullable|email|max:255',
            'status'     => 'sometimes|in:new,active,qualified,nurturing,closed,archived',
        ]);

        $contact = Contact::create([
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'phone'             => $data['phone'] ?? null,
            'email'             => $data['email'] ?? null,
            'status'            => $data['status'] ?? 'new',
            'agency_id'         => $request->user()->agency_id,
            'assigned_agent_id' => $request->user()->id,
        ]);

        return response()->json($contact, 201);
    }

    public function addNote(Request $request, Contact $contact): JsonResponse
    {
        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $activity = ContactActivity::create([
            'agency_id'   => $request->user()->agency_id,
            'contact_id'  => $contact->id,
            'user_id'     => $request->user()->id,
            'type'        => 'note',
            'description' => $request->note,
            'occurred_at' => now(),
        ]);

        return response()->json($activity, 201);
    }

    public function calls(Contact $contact): JsonResponse
    {
        $calls = $contact->calls()
            ->with('summary')
            ->latest('started_at')
            ->paginate(20);

        return response()->json($calls);
    }
}
