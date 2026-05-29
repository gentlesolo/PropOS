<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = Contact::when($request->search, fn($q) => $q->where(function ($s) use ($request) {
            $s->where('first_name', 'like', "%{$request->search}%")
              ->orWhere('last_name', 'like', "%{$request->search}%")
              ->orWhere('email', 'like', "%{$request->search}%")
              ->orWhere('phone', 'like', "%{$request->search}%");
        }))
        ->when($request->type, fn($q) => $q->where('type', $request->type))
        ->latest()
        ->paginate($request->per_page ?? 20);

        return response()->json($contacts);
    }

    public function show(Contact $contact)
    {
        return response()->json($contact->load('agent', 'activities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'type' => 'required|in:buyer,seller,landlord,tenant,investor,referral_partner',
            'source' => 'nullable|string|max:100',
        ]);

        $contact = Contact::create([
            ...$data,
            'agency_id' => $request->user()->agency_id,
            'status' => 'new',
        ]);

        return response()->json($contact, 201);
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'sometimes|in:new,active,qualified,nurturing,closed,archived',
            'notes' => 'nullable|string',
        ]);

        $contact->update($data);
        return response()->json($contact->fresh());
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return response()->json(['message' => 'Contact deleted.']);
    }
}
