<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Seller Report — {{ $property->address_line_1 }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #1a1a2e; background: #fff; }
        .header { background: #1E40AF; color: #fff; padding: 32px 40px; }
        .header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .header p { font-size: 12px; opacity: 0.85; }
        .agency-name { font-size: 11px; opacity: 0.7; margin-top: 8px; }
        .content { padding: 32px 40px; }
        .section { margin-bottom: 28px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6B7280; border-bottom: 1px solid #E5E7EB; padding-bottom: 6px; margin-bottom: 14px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .stat-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 14px 16px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: 800; color: #1E40AF; }
        .stat-label { font-size: 10px; color: #6B7280; margin-top: 3px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }
        .property-table td { padding: 7px 0; border-bottom: 1px solid #F3F4F6; vertical-align: top; }
        .property-table td:first-child { color: #6B7280; width: 140px; font-size: 12px; }
        .property-table td:last-child { font-weight: 600; font-size: 12px; }
        .narrative-box { background: #EFF6FF; border-left: 4px solid #1E40AF; border-radius: 0 6px 6px 0; padding: 16px 20px; font-size: 12px; line-height: 1.7; color: #374151; }
        .portal-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #F3F4F6; font-size: 12px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; }
        .badge-green { background: #D1FAE5; color: #065F46; }
        .badge-blue  { background: #DBEAFE; color: #1E3A8A; }
        .health-bar-bg { background: #E5E7EB; border-radius: 4px; height: 8px; margin-top: 4px; }
        .health-bar-fill { height: 8px; border-radius: 4px; background: #1E40AF; }
        .footer { margin-top: 40px; padding: 20px 40px; background: #F9FAFB; border-top: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center; }
        .footer-text { font-size: 10px; color: #9CA3AF; }
        .agent-block { text-align: right; }
        .agent-name { font-weight: 700; font-size: 12px; color: #374151; }
        .agent-contact { font-size: 10px; color: #6B7280; margin-top: 2px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Seller Progress Report</h1>
    <p>{{ $property->address_line_1 }}, {{ $property->city }} &mdash; {{ now()->format('F Y') }}</p>
    <p class="agency-name">{{ $listing->agency->name ?? 'PropOS Agency' }}</p>
</div>

<div class="content">

    <!-- Stats row -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $daysOnMarket }}</div>
            <div class="stat-label">Days on Market</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $viewingsTotal }}</div>
            <div class="stat-label">Total Viewings</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $inquiryTotal }}</div>
            <div class="stat-label">Portal Inquiries</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $listing->health_score ?? '—' }}</div>
            <div class="stat-label">Listing Health</div>
        </div>
    </div>

    <!-- Property Details -->
    <div class="section">
        <div class="section-title">Property Details</div>
        <table class="property-table" width="100%">
            <tr><td>Address</td><td>{{ $property->address_line_1 }}, {{ $property->city }}</td></tr>
            <tr><td>Type</td><td>{{ ucfirst($property->property_type ?? '—') }}</td></tr>
            <tr><td>Bedrooms</td><td>{{ $property->bedrooms ?? '—' }}</td></tr>
            <tr><td>Bathrooms</td><td>{{ $property->bathrooms ?? '—' }}</td></tr>
            <tr><td>Floor Area</td><td>{{ $property->floor_area_sqm ? $property->floor_area_sqm . ' m²' : '—' }}</td></tr>
            <tr><td>Listing Price</td><td style="color:#1E40AF;font-size:14px;">{{ number_format((float) $listing->listing_price) }}</td></tr>
            <tr><td>Mandate Type</td><td>{{ ucfirst($listing->mandate_type) }}</td></tr>
            @if($listing->mandate_end_date)
            <tr><td>Mandate Expires</td><td>{{ $listing->mandate_end_date->format('M j, Y') }}</td></tr>
            @endif
        </table>
    </div>

    <!-- Listing Health -->
    @if($listing->health_score)
    <div class="section">
        <div class="section-title">Listing Health Score</div>
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="flex:1;">
                <div class="health-bar-bg">
                    <div class="health-bar-fill" style="width:{{ $listing->health_score }}%;background:{{ $listing->health_score >= 70 ? '#10B981' : ($listing->health_score >= 40 ? '#F59E0B' : '#EF4444') }};"></div>
                </div>
            </div>
            <span style="font-weight:700;font-size:16px;color:{{ $listing->health_score >= 70 ? '#065F46' : ($listing->health_score >= 40 ? '#92400E' : '#991B1B') }};">
                {{ $listing->health_score }}/100
            </span>
        </div>
    </div>
    @endif

    <!-- Portal Syndication -->
    @if($portalSyncs->isNotEmpty())
    <div class="section">
        <div class="section-title">Portal Performance</div>
        @foreach($portalSyncs as $sync)
        <div class="portal-row">
            <span>{{ $sync->portal->name ?? $sync->portal_id }}</span>
            <div style="display:flex;gap:8px;align-items:center;">
                @if($sync->inquiries_count ?? 0)
                <span style="font-size:11px;color:#6B7280;">{{ $sync->inquiries_count }} inquiries</span>
                @endif
                <span class="badge badge-green">Live</span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Agent Narrative -->
    <div class="section">
        <div class="section-title">Agent Commentary</div>
        <div class="narrative-box">{{ $narrative }}</div>
    </div>

    <!-- Next Steps -->
    <div class="section">
        <div class="section-title">This Week</div>
        <ul style="padding-left:16px;font-size:12px;line-height:2;color:#374151;">
            <li>Continue promoting on all active portals</li>
            @if($viewingsTotal == 0)
            <li>Schedule first viewings — contact agent to arrange</li>
            @else
            <li>Follow up with buyers from {{ $viewingsComplete }} completed viewings</li>
            @endif
            @if(($listing->health_score ?? 100) < 60)
            <li style="color:#DC2626;font-weight:600;">Listing health is low — discuss price review with your agent</li>
            @endif
        </ul>
    </div>

</div>

<div class="footer">
    <div class="footer-text">
        Generated {{ $generatedAt }} &middot; Powered by PropOS
    </div>
    <div class="agent-block">
        <div class="agent-name">{{ $listing->agent?->name ?? 'Your Agent' }}</div>
        @if($listing->agent?->phone)
        <div class="agent-contact">{{ $listing->agent->phone }}</div>
        @endif
        @if($listing->agent?->email)
        <div class="agent-contact">{{ $listing->agent->email }}</div>
        @endif
    </div>
</div>

</body>
</html>
