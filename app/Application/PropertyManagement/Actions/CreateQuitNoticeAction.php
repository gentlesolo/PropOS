<?php

namespace App\Application\PropertyManagement\Actions;

use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\QuitNotice;

class CreateQuitNoticeAction
{
    public function execute(array $data): QuitNotice
    {
        $lease = Lease::findOrFail($data['lease_id']);

        return QuitNotice::create([
            'agency_id'       => $lease->agency_id,
            'lease_id'        => $lease->id,
            'tenant_id'       => $lease->tenant_id,
            'issued_by'       => auth()->id(),
            'issue_date'      => now()->toDateString(),
            'vacate_by_date'  => $data['vacate_by_date'],
            'reason'          => $data['reason'],
            'notice_body'     => $data['notice_body'],
            'ai_draft'        => $data['ai_draft'] ?? null,
            'delivery_method' => $data['delivery_method'] ?? 'email',
            'internal_notes'  => $data['internal_notes'] ?? null,
            'status'          => 'drafted',
        ]);
    }
}
