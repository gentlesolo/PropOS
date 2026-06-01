<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileTenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::with('contact:id,first_name,last_name,phone,email', 'listing.property:id,address_line_1,city', 'activeLease')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->whereHas('contact', fn ($sq) =>
                $sq->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $tenants->map(fn ($t) => $this->formatTenant($t)),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page'    => $tenants->lastPage(),
                'total'        => $tenants->total(),
            ],
        ]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load([
            'contact',
            'listing.property',
            'activeLease.rentPayments' => fn ($q) => $q->orderByDesc('due_date')->limit(6),
            'agent:id,name,phone,email',
        ]);

        $lastPayments = $tenant->activeLease?->rentPayments ?? collect();

        return response()->json([
            'data' => array_merge($this->formatTenant($tenant), [
                'contact'      => $tenant->contact?->only(['id', 'first_name', 'last_name', 'phone', 'email', 'id_number']),
                'agent'        => $tenant->agent?->only(['id', 'name', 'phone', 'email']),
                'active_lease' => $tenant->activeLease ? $this->formatLease($tenant->activeLease) : null,
                'recent_payments' => $lastPayments->map(fn ($p) => [
                    'id'          => $p->id,
                    'reference'   => $p->reference,
                    'amount_due'  => (float) $p->amount_due,
                    'amount_paid' => $p->amount_paid ? (float) $p->amount_paid : null,
                    'status'      => $p->status,
                    'due_date'    => $p->due_date->toDateString(),
                    'paid_date'   => $p->paid_date?->toDateString(),
                ]),
            ]),
        ]);
    }

    private function formatTenant(Tenant $tenant): array
    {
        return [
            'id'             => $tenant->id,
            'full_name'      => $tenant->contact?->full_name,
            'status'         => $tenant->status,
            'property'       => $tenant->listing?->property
                ? "{$tenant->listing->property->address_line_1}, {$tenant->listing->property->city}"
                : null,
            'monthly_rent'   => $tenant->activeLease ? (float) $tenant->activeLease->monthly_rent : null,
            'lease_end_date' => $tenant->activeLease?->end_date?->toDateString(),
            'fica_count'     => count($tenant->fica_documents ?? []),
            'portal_token'   => $tenant->portal_token,
        ];
    }

    private function formatLease(\App\Infrastructure\Persistence\Models\Lease $lease): array
    {
        return [
            'id'                 => $lease->id,
            'reference'          => $lease->reference,
            'status'             => $lease->status,
            'monthly_rent'       => (float) $lease->monthly_rent,
            'deposit_amount'     => $lease->deposit_amount ? (float) $lease->deposit_amount : null,
            'escalation_percent' => $lease->escalation_percent,
            'payment_day'        => $lease->payment_day,
            'start_date'         => $lease->start_date->toDateString(),
            'end_date'           => $lease->end_date->toDateString(),
            'days_until_expiry'  => $lease->daysUntilExpiry,
            'outstanding_balance'=> $lease->outstandingBalance,
        ];
    }
}
