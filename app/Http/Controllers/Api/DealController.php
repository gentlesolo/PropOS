<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $deals = Deal::with(['contact', 'stage', 'agent', 'listing.property'])
            ->when($request->stage_id, fn($q) => $q->where('pipeline_stage_id', $request->stage_id))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($deals);
    }

    public function show(Deal $deal)
    {
        return response()->json($deal->load('contact', 'stage', 'agent', 'listing.property', 'activities'));
    }

    public function update(Request $request, Deal $deal)
    {
        $data = $request->validate([
            'pipeline_stage_id' => 'sometimes|exists:pipeline_stages,id',
            'value' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $deal->update($data);
        return response()->json($deal->fresh()->load('stage'));
    }
}
