<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt — {{ $payment->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; }

        .header { background: {{ $color }}; color: #fff; padding: 28px 40px; }
        .header-title { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
        .header-sub { font-size: 11px; opacity: 0.85; margin-top: 4px; }
        .header-agency { font-size: 10px; opacity: 0.7; margin-top: 6px; }

        .content { padding: 32px 40px; }

        .ref-bar { display: flex; justify-content: space-between; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; font-size: 11px; color: #6b7280; }
        .ref-bar strong { color: #374151; }

        .amount-block { border-radius: 10px; overflow: hidden; margin-bottom: 24px; border: 1px solid #e5e7eb; }
        .amount-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid #f3f4f6; font-size: 12px; }
        .amount-row:last-child { border-bottom: none; }
        .amount-row.total { background: {{ $color }}; color: #fff; font-weight: 700; font-size: 14px; }
        .amount-row.balance-due { background: #fef2f2; color: #991b1b; font-weight: 700; }
        .amount-row.balance-nil { background: #f0fdf4; color: #166534; font-weight: 700; }
        .amount-label { color: inherit; opacity: 0.85; }
        .amount-value { font-weight: 600; }

        .section { margin-bottom: 22px; }
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 12px; }

        .detail-table { width: 100%; }
        .detail-table td { padding: 6px 0; border-bottom: 1px solid #f3f4f6; vertical-align: top; font-size: 11px; }
        .detail-table td:first-child { color: #6b7280; width: 160px; }
        .detail-table td:last-child { font-weight: 600; color: #111827; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-paid    { background: #d1fae5; color: #065f46; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-pending { background: #eff6ff; color: #1e40af; }

        .footer { margin-top: 40px; padding: 18px 40px; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-left { font-size: 10px; color: #9ca3af; line-height: 1.6; }
        .footer-right { text-align: right; font-size: 11px; }
        .agency-name { font-weight: 700; color: #374151; }
        .agency-contact { font-size: 10px; color: #6b7280; margin-top: 2px; }
    </style>
</head>
<body>

@php
    $lease    = $payment->lease;
    $contact  = $lease?->tenant?->contact ?? $lease?->contact;
    $property = $lease?->listing?->property;
    $agency   = $payment->agency;

    $address    = $property ? "{$property->address_line_1}, {$property->city}" : 'the property';
    $amountDue  = (float) $payment->amount_due;
    $amountPaid = (float) ($payment->amount_paid ?? 0);
    $balance    = round($amountDue - $amountPaid, 2);
    $currSymbol = $agency?->currency_symbol ?? '₦';
    $paidDate   = $payment->paid_date
        ? \Carbon\Carbon::parse($payment->paid_date)->format('d M Y')
        : now()->format('d M Y');
    $statusClass = 'status-' . $payment->status;
@endphp

<div class="header">
    <div class="header-title">{{ $payment->status === 'partial' ? 'Partial Payment Receipt' : 'Payment Receipt' }}</div>
    <div class="header-sub">{{ $address }}</div>
    <div class="header-agency">{{ $agency?->name ?? 'Property Management' }}</div>
</div>

<div class="content">

    <div class="ref-bar">
        <div><strong>Reference:</strong> {{ $payment->reference }}</div>
        <div><strong>Period:</strong> {{ $payment->due_date->format('F Y') }}</div>
        <div><strong>Date:</strong> {{ $paidDate }}</div>
        <div><span class="status-badge {{ $statusClass }}">{{ ucfirst($payment->status) }}</span></div>
    </div>

    <!-- Amount Summary -->
    <div class="amount-block">
        <div class="amount-row">
            <span class="amount-label">Amount Due</span>
            <span class="amount-value">{{ $currSymbol }}{{ number_format($amountDue, 2) }}</span>
        </div>
        <div class="amount-row total">
            <span class="amount-label">Amount Paid</span>
            <span class="amount-value">{{ $currSymbol }}{{ number_format($amountPaid, 2) }}</span>
        </div>
        @if($balance > 0)
        <div class="amount-row balance-due">
            <span class="amount-label">Outstanding Balance</span>
            <span class="amount-value">{{ $currSymbol }}{{ number_format($balance, 2) }}</span>
        </div>
        @else
        <div class="amount-row balance-nil">
            <span class="amount-label">Balance</span>
            <span class="amount-value">Fully Paid</span>
        </div>
        @endif
    </div>

    <!-- Payment Details -->
    <div class="section">
        <div class="section-title">Payment Details</div>
        <table class="detail-table" width="100%">
            <tr><td>Tenant</td><td>{{ $contact?->full_name ?? '—' }}</td></tr>
            <tr><td>Property</td><td>{{ $address }}</td></tr>
            <tr><td>Lease Reference</td><td>{{ $lease?->reference ?? '—' }}</td></tr>
            <tr><td>Payment Period</td><td>{{ $payment->due_date->format('F Y') }}</td></tr>
            <tr><td>Due Date</td><td>{{ $payment->due_date->format('d M Y') }}</td></tr>
            <tr><td>Payment Date</td><td>{{ $paidDate }}</td></tr>
            <tr><td>Payment Method</td><td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? 'EFT')) }}</td></tr>
            <tr><td>Status</td><td><span class="status-badge {{ $statusClass }}">{{ ucfirst($payment->status) }}</span></td></tr>
        </table>
    </div>

    @if($payment->notes ?? false)
    <div class="section">
        <div class="section-title">Notes</div>
        <div style="font-size:11px;color:#6b7280;">{{ $payment->notes }}</div>
    </div>
    @endif

</div>

<div class="footer">
    <div class="footer-left">
        Generated {{ now()->format('d M Y H:i') }} &middot; {{ $agency?->name ?? 'VillaCRM' }} &middot; Ref: {{ $payment->reference }}<br>
        @if($agency?->address){{ $agency->address }}@endif
    </div>
    <div class="footer-right">
        <div class="agency-name">{{ $agency?->name ?? 'Property Management' }}</div>
        @if($agency?->phone)<div class="agency-contact">{{ $agency->phone }}</div>@endif
        @if($agency?->email)<div class="agency-contact">{{ $agency->email }}</div>@endif
    </div>
</div>

</body>
</html>
