<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Application\AI\Actions\GenerateQuitNoticeContentAction;
use App\Application\PropertyManagement\Actions\CreateQuitNoticeAction;
use App\Application\PropertyManagement\Actions\SendQuitNoticeAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\QuitNotice;
use App\Infrastructure\Persistence\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileQuitNoticeController extends Controller
{
    public function forTenant(Tenant $tenant): JsonResponse
    {
        abort_unless(
            $tenant->agency_id === request()->user()->agency_id,
            403,
            'Access denied.'
        );

        $notices = QuitNotice::with('issuedBy:id,name')
            ->where('tenant_id', $tenant->id)
            ->where('agency_id', request()->user()->agency_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($n) => $this->formatNotice($n));

        return response()->json(['data' => $notices]);
    }

    public function show(QuitNotice $quitNotice): JsonResponse
    {
        $this->authorizeNotice($quitNotice);
        $quitNotice->load('issuedBy:id,name', 'lease:id,reference');

        return response()->json(['data' => $this->formatNotice($quitNotice, detail: true)]);
    }

    public function store(Request $request, CreateQuitNoticeAction $action): JsonResponse
    {
        $request->validate([
            'lease_id'        => 'required|exists:leases,id',
            'vacate_by_date'  => 'required|date|after:today',
            'reason'          => 'required|string|min:5',
            'notice_body'     => 'required|string|min:20',
            'delivery_method' => 'required|in:email,hand_delivered,registered_post,email_and_post',
            'internal_notes'  => 'nullable|string',
        ]);

        $lease = Lease::where('agency_id', $request->user()->agency_id)
            ->findOrFail($request->lease_id);

        $notice = $action->execute([
            'lease_id'        => $lease->id,
            'vacate_by_date'  => $request->vacate_by_date,
            'reason'          => $request->reason,
            'notice_body'     => $request->notice_body,
            'ai_draft'        => $request->notice_body,
            'delivery_method' => $request->delivery_method,
            'internal_notes'  => $request->internal_notes,
        ]);

        return response()->json(['data' => $this->formatNotice($notice)], 201);
    }

    public function generateContent(Request $request, GenerateQuitNoticeContentAction $action): JsonResponse
    {
        $request->validate([
            'lease_id'       => 'required|exists:leases,id',
            'reason'         => 'required|string|min:5',
            'vacate_by_date' => 'required|date|after:today',
        ]);

        $lease = Lease::with('tenant.contact', 'listing.property', 'agency')
            ->where('agency_id', $request->user()->agency_id)
            ->findOrFail($request->lease_id);

        $body = $action->execute($lease, $request->reason, $request->vacate_by_date);

        return response()->json(['notice_body' => $body]);
    }

    public function send(QuitNotice $quitNotice, SendQuitNoticeAction $action): JsonResponse
    {
        $this->authorizeNotice($quitNotice);

        abort_unless(
            in_array($quitNotice->status, ['drafted', 'disputed']),
            422,
            'Only drafted or disputed notices can be sent.'
        );

        $quitNotice->load('lease.tenant.contact', 'lease.listing.property', 'issuedBy', 'agency');
        $notice = $action->execute($quitNotice);

        return response()->json(['data' => $this->formatNotice($notice)]);
    }

    public function withdraw(QuitNotice $quitNotice): JsonResponse
    {
        $this->authorizeNotice($quitNotice);

        abort_unless(
            in_array($quitNotice->status, ['drafted', 'sent', 'acknowledged', 'disputed']),
            422,
            'This notice cannot be withdrawn.'
        );

        $quitNotice->update(['status' => 'withdrawn']);

        return response()->json(['data' => $this->formatNotice($quitNotice->fresh())]);
    }

    private function formatNotice(QuitNotice $notice, bool $detail = false): array
    {
        $base = [
            'id'                 => $notice->id,
            'reference'          => $notice->reference,
            'status'             => $notice->status,
            'reason'             => $notice->reason,
            'vacate_by_date'     => $notice->vacate_by_date?->toDateString(),
            'issue_date'         => $notice->issue_date?->toDateString(),
            'delivery_method'    => $notice->delivery_method,
            'sent_at'            => $notice->sent_at?->toISOString(),
            'notice_period_days' => $notice->notice_period_days,
            'issued_by_name'     => $notice->issuedBy?->name,
        ];

        if ($detail) {
            $base['notice_body']    = $notice->notice_body;
            $base['internal_notes'] = $notice->internal_notes;
            $base['lease_ref']      = $notice->lease?->reference;
        }

        return $base;
    }

    private function authorizeNotice(QuitNotice $notice): void
    {
        abort_unless(
            $notice->agency_id === request()->user()->agency_id,
            403,
            'Access denied.'
        );
    }
}
