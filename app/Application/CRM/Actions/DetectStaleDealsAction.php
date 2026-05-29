<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Deal;
use Illuminate\Database\Eloquent\Collection;

class DetectStaleDealsAction
{
    public function execute(int $agencyId, int $staleAfterDays = 14): Collection
    {
        return Deal::with(['contact', 'stage', 'agent'])
            ->where('agency_id', $agencyId)
            ->whereHas('stage', fn($q) => $q->where('is_won', false)->where('is_lost', false))
            ->where('updated_at', '<', now()->subDays($staleAfterDays))
            ->orderBy('updated_at')
            ->get();
    }
}
