<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quit Notice — {{ $quitNotice->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; }

        .header { background: {{ $quitNotice->agency?->primary_color ?? '#10B981' }}; color: #fff; padding: 28px 40px; }
        .header-title { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
        .header-sub { font-size: 11px; opacity: 0.85; margin-top: 4px; }
        .header-agency { font-size: 10px; opacity: 0.7; margin-top: 6px; }

        .content { padding: 32px 40px; }

        .notice-ref-bar { display: flex; justify-content: space-between; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; font-size: 11px; color: #6b7280; }
        .notice-ref-bar strong { color: #374151; }

        .section { margin-bottom: 22px; }
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 12px; }

        .detail-table { width: 100%; }
        .detail-table td { padding: 6px 0; border-bottom: 1px solid #f3f4f6; vertical-align: top; font-size: 11px; }
        .detail-table td:first-child { color: #6b7280; width: 160px; }
        .detail-table td:last-child { font-weight: 600; color: #111827; }

        .notice-body { line-height: 1.8; font-size: 12px; color: #374151; white-space: pre-wrap; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-sent         { background: #dbeafe; color: #1e40af; }
        .status-drafted      { background: #f3f4f6; color: #4b5563; }
        .status-acknowledged { background: #fef3c7; color: #92400e; }
        .status-disputed     { background: #fee2e2; color: #991b1b; }
        .status-completed    { background: #d1fae5; color: #065f46; }
        .status-withdrawn    { background: #f3f4f6; color: #9ca3af; }

        .vacate-highlight { background: #fff7ed; border-left: 4px solid #f97316; padding: 12px 16px; border-radius: 0 6px 6px 0; margin: 20px 0; }
        .vacate-highlight .label { font-size: 10px; color: #9a3412; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; }
        .vacate-highlight .date  { font-size: 20px; font-weight: 800; color: #c2410c; margin-top: 2px; }

        .footer { margin-top: 40px; padding: 18px 40px; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-left { font-size: 10px; color: #9ca3af; }
        .footer-right { text-align: right; font-size: 11px; }
        .issuer-name { font-weight: 700; color: #374151; }
        .issuer-contact { font-size: 10px; color: #6b7280; margin-top: 2px; }

        .watermark-box { border: 2px solid {{ $quitNotice->agency?->primary_color ?? '#10B981' }}; border-radius: 8px; padding: 8px 16px; display: inline-block; margin-bottom: 20px; }
        .watermark-box span { font-size: 11px; font-weight: 700; color: {{ $quitNotice->agency?->primary_color ?? '#10B981' }}; text-transform: uppercase; letter-spacing: 0.1em; }
    </style>
</head>
<body>

@php
    $contact  = $quitNotice->lease?->tenant?->contact;
    $property = $quitNotice->lease?->listing?->property;
    $issuer   = $quitNotice->issuedBy;
    $agency   = $quitNotice->agency;
    $address  = $property ? "{$property->address_line_1}, {$property->city}" : 'the leased premises';
    $statusClass = 'status-' . $quitNotice->status;
@endphp

<div class="header">
    <div class="header-title">Quit Notice</div>
    <div class="header-sub">{{ $address }}</div>
    <div class="header-agency">{{ $agency?->name ?? 'Property Management' }}</div>
</div>

<div class="content">

    <div class="notice-ref-bar">
        <div><strong>Reference:</strong> {{ $quitNotice->reference }}</div>
        <div><strong>Lease Ref:</strong> {{ $quitNotice->lease?->reference }}</div>
        <div><strong>Issue Date:</strong> {{ $quitNotice->issue_date->format('d M Y') }}</div>
        <div>
            <span class="status-badge {{ $statusClass }}">{{ ucfirst($quitNotice->status) }}</span>
        </div>
    </div>

    <!-- Vacate By Highlight -->
    <div class="vacate-highlight">
        <div class="label">Required Vacate Date</div>
        <div class="date">{{ $quitNotice->vacate_by_date->format('d F Y') }}</div>
    </div>

    <!-- Party Details -->
    <div class="section">
        <div class="section-title">Notice Details</div>
        <table class="detail-table" width="100%">
            <tr><td>Tenant</td><td>{{ $contact?->full_name ?? '—' }}</td></tr>
            @if($contact?->email)
            <tr><td>Email</td><td>{{ $contact->email }}</td></tr>
            @endif
            @if($contact?->phone)
            <tr><td>Phone</td><td>{{ $contact->phone }}</td></tr>
            @endif
            <tr><td>Property</td><td>{{ $address }}</td></tr>
            <tr><td>Reason</td><td>{{ $quitNotice->reason }}</td></tr>
            <tr><td>Delivery Method</td><td>{{ ucfirst(str_replace('_', ' ', $quitNotice->delivery_method)) }}</td></tr>
            @if($quitNotice->sent_at)
            <tr><td>Sent At</td><td>{{ $quitNotice->sent_at->format('d M Y H:i') }}</td></tr>
            @endif
        </table>
    </div>

    <!-- Notice Body -->
    <div class="section">
        <div class="section-title">Notice Content</div>
        <div class="notice-body">{{ $quitNotice->notice_body }}</div>
    </div>

    @if($quitNotice->internal_notes)
    <div class="section">
        <div class="section-title">Internal Notes</div>
        <div style="font-size:11px;color:#6b7280;font-style:italic;">{{ $quitNotice->internal_notes }}</div>
    </div>
    @endif

</div>

<div class="footer">
    <div class="footer-left">
        Generated {{ now()->format('d M Y H:i') }} &middot; {{ $agency?->name ?? 'VillaCRM' }} &middot; Ref: {{ $quitNotice->reference }}
    </div>
    <div class="footer-right">
        <div class="issuer-name">{{ $issuer?->name ?? 'Property Manager' }}</div>
        @if($issuer?->email)
        <div class="issuer-contact">{{ $issuer->email }}</div>
        @endif
        @if($issuer?->phone)
        <div class="issuer-contact">{{ $issuer->phone }}</div>
        @endif
    </div>
</div>

</body>
</html>
