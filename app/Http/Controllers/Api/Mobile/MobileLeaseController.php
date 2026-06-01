<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Application\PropertyManagement\Actions\ProcessRentPaymentAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Lease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileLeaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $leases = Lease::with('tenant.contact:id,first_name,last_name,phone', 'listing.property:id,address_line_1,city')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('end_date')
            ->paginate(20);

        return response()->json([
            'data' => $leases->map(fn ($l) => $this->formatLease($l)),
            'meta' => [
                'current_page' => $leases->currentPage(),
                'last_page'    => $leases->lastPage(),
                'total'        => $leases->total(),
            ],
        ]);
    }

    public function show(Lease $lease): JsonResponse
    {
        $lease->load([
            'tenant.contact',
            'listing.property',
            'rentPayments' => fn ($q) => $q->orderByDesc('due_date'),
        ]);

        return response()->json([
            'data' => array_merge($this->formatLease($lease), [
                'tenant'        => $lease->tenant?->contact?->only(['id', 'first_name', 'last_name', 'phone', 'email']),
                'property'      => $lease->listing?->property?->only(['id', 'address_line_1', 'city']),
                'rent_payments' => $lease->rentPayments->map(fn ($p) => [
                    'id'          => $p->id,
                    'reference'   => $p->reference,
                    'amount_due'  => (float) $p->amount_due,
                    'amount_paid' => $p->amount_paid ? (float) $p->amount_paid : null,
                    'status'      => $p->status,
                    'due_date'    => $p->due_date->toDateString(),
                    'paid_date'   => $p->paid_date?->toDateString(),
                    'method'      => $p->payment_method,
                ]),
            ]),
        ]);
    }

    public function recordPayment(Request $request, Lease $lease, ProcessRentPaymentAction $action): JsonResponse
    {
        $validated = $request->validate([
            'amount_paid'    => 'required|numeric|min:0.01',
            'paid_date'      => 'required|date',
            'payment_method' => 'required|in:eft,cash,card,cheque',
            'notes'          => 'nullable|string|max:500',
        ]);

        $payment = $action->execute(
            $lease,
            (float) $validated['amount_paid'],
            $validated['paid_date'],
            $validated['payment_method'],
            $validated['notes'] ?? null,
        );

        return response()->json([
            'message' => 'Payment recorded.',
            'data'    => [
                'id'          => $payment->id,
                'reference'   => $payment->reference,
                'amount_due'  => (float) $payment->amount_due,
                'amount_paid' => (float) ($payment->amount_paid ?? 0),
                'status'      => $payment->status,
                'due_date'    => $payment->due_date->toDateString(),
                'paid_date'   => $payment->paid_date?->toDateString(),
            ],
        ], 201);
    }

    private function formatLease(Lease $lease): array
    {
        return [
            'id'                 => $lease->id,
            'reference'          => $lease->reference,
            'status'             => $lease->status,
            'tenant_name'        => $lease->tenant?->contact?->full_name,
            'property'           => $lease->listing?->property
                ? "{$lease->listing->property->address_line_1}, {$lease->listing->property->city}"
                : null,
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
