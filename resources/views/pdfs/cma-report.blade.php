<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CMA Report — {{ $report->subject_address }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #1a1a2e; background: #fff; }
        .header { background: #1E40AF; color: #fff; padding: 32px 40px; }
        .header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .header p { font-size: 12px; opacity: 0.85; }
        .header .sub { font-size: 11px; opacity: 0.7; margin-top: 6px; }
        .content { padding: 32px 40px; }
        .section { margin-bottom: 28px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6B7280; border-bottom: 1px solid #E5E7EB; padding-bottom: 6px; margin-bottom: 14px; }
        .value-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
        .value-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px; text-align: center; }
        .value-card.highlight { background: #EFF6FF; border-color: #BFDBFE; }
        .value-label { font-size: 10px; color: #6B7280; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .value-amount { font-size: 20px; font-weight: 800; color: #1E40AF; }
        .value-amount.large { font-size: 24px; }
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table td { padding: 7px 0; border-bottom: 1px solid #F3F4F6; vertical-align: top; font-size: 12px; }
        .detail-table td:first-child { color: #6B7280; width: 160px; }
        .detail-table td:last-child { font-weight: 600; }
        .comp-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .comp-table th { background: #F3F4F6; text-align: left; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6B7280; border-bottom: 2px solid #E5E7EB; }
        .comp-table td { padding: 9px 10px; border-bottom: 1px solid #F3F4F6; }
        .comp-table tr:last-child td { border-bottom: none; }
        .comp-table td:nth-child(2) { font-weight: 700; color: #1E40AF; }
        .summary-box { background: #EFF6FF; border-left: 4px solid #1E40AF; border-radius: 0 6px 6px 0; padding: 16px 20px; font-size: 12px; line-height: 1.7; color: #374151; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 600; }
        .badge-blue { background: #DBEAFE; color: #1E3A8A; }
        .footer { margin-top: 40px; padding: 20px 40px; background: #F9FAFB; border-top: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center; }
        .footer-text { font-size: 10px; color: #9CA3AF; }
        .prep-block { text-align: right; }
        .prep-name { font-weight: 700; font-size: 12px; color: #374151; }
        .prep-detail { font-size: 10px; color: #6B7280; margin-top: 2px; }
        .divider { height: 1px; background: #E5E7EB; margin: 20px 0; }
        .avg-row { background: #F0FDF4; font-weight: 700; }
        .avg-row td { color: #065F46 !important; padding: 9px 10px; border-top: 2px solid #A7F3D0; }
    </style>
</head>
<body>

<div class="header">
    <h1>Comparative Market Analysis</h1>
    <p>{{ $report->subject_address }}</p>
    <p class="sub">
        Prepared {{ $report->created_at->format('F j, Y') }}
        @if($report->contact) &mdash; For: {{ $report->contact->full_name }} @endif
    </p>
</div>

<div class="content">

    {{-- Value Summary --}}
    <div class="value-grid">
        <div class="value-card">
            <div class="value-label">Estimated Value Low</div>
            <div class="value-amount">
                @if($report->estimated_value_low)
                    ₦{{ number_format($report->estimated_value_low) }}
                @else
                    —
                @endif
            </div>
        </div>
        <div class="value-card highlight">
            <div class="value-label">Recommended List Price</div>
            <div class="value-amount large">
                @if($report->recommended_list_price)
                    ₦{{ number_format($report->recommended_list_price) }}
                @else
                    —
                @endif
            </div>
        </div>
        <div class="value-card">
            <div class="value-label">Estimated Value High</div>
            <div class="value-amount">
                @if($report->estimated_value_high)
                    ₦{{ number_format($report->estimated_value_high) }}
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    {{-- Report Details --}}
    <div class="section">
        <div class="section-title">Report Details</div>
        <table class="detail-table">
            <tr><td>Report Title</td><td>{{ $report->title }}</td></tr>
            <tr><td>Subject Property</td><td>{{ $report->subject_address }}</td></tr>
            @if($report->listing)
                <tr><td>Linked Listing</td><td>{{ $report->listing->property->address_line_1 ?? '—' }}</td></tr>
            @endif
            @if($report->contact)
                <tr><td>Prepared For</td><td>{{ $report->contact->full_name }}</td></tr>
            @endif
            <tr><td>Prepared By</td><td>{{ $report->createdBy->name ?? '—' }}</td></tr>
            <tr><td>Date</td><td>{{ $report->created_at->format('M j, Y') }}</td></tr>
            @if(count($report->comparable_sales ?? []) > 0)
                <tr><td>Comparables Used</td><td>{{ count($report->comparable_sales) }}</td></tr>
            @endif
        </table>
    </div>

    {{-- Comparable Sales --}}
    @if(count($report->comparable_sales ?? []) > 0)
    <div class="section">
        <div class="section-title">Comparable Sales</div>
        <table class="comp-table">
            <thead>
                <tr>
                    <th>Address</th>
                    <th>Sale Price</th>
                    <th>Date</th>
                    <th>Beds</th>
                    <th>Size (m²)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $compSales = $report->comparable_sales;
                    $totalPrice = collect($compSales)->sum(fn($c) => (float) ($c['sale_price'] ?? 0));
                    $avgPrice = count($compSales) > 0 ? $totalPrice / count($compSales) : 0;
                @endphp
                @foreach($compSales as $comp)
                <tr>
                    <td>{{ $comp['address'] ?? '—' }}</td>
                    <td>₦{{ number_format((float)($comp['sale_price'] ?? 0)) }}</td>
                    <td>{{ $comp['sale_date'] ? \Carbon\Carbon::parse($comp['sale_date'])->format('M Y') : '—' }}</td>
                    <td>{{ $comp['bedrooms'] ?? '—' }}</td>
                    <td>{{ $comp['sqm'] ?? '—' }}</td>
                </tr>
                @endforeach
                @if(count($compSales) > 1)
                <tr class="avg-row">
                    <td>Average</td>
                    <td>₦{{ number_format($avgPrice) }}</td>
                    <td colspan="3"></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    {{-- Market Stats --}}
    @if(count($report->market_stats ?? []) > 0)
    <div class="section">
        <div class="section-title">Market Statistics</div>
        <table class="detail-table">
            @foreach($report->market_stats as $key => $value)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    {{-- Analyst Summary --}}
    @if($report->summary)
    <div class="section">
        <div class="section-title">Analyst Commentary</div>
        <div class="summary-box">{{ $report->summary }}</div>
    </div>
    @endif

    {{-- Pricing Guidance --}}
    @if($report->recommended_list_price)
    <div class="section">
        <div class="section-title">Pricing Guidance</div>
        <ul style="padding-left:16px;font-size:12px;line-height:2;color:#374151;">
            <li>Recommended list price of <strong>₦{{ number_format($report->recommended_list_price) }}</strong> is based on {{ count($report->comparable_sales ?? []) }} comparable sales.</li>
            @if($report->estimated_value_low && $report->estimated_value_high)
            <li>Market value range: <strong>₦{{ number_format($report->estimated_value_low) }}</strong> – <strong>₦{{ number_format($report->estimated_value_high) }}</strong></li>
            @endif
            <li>Pricing competitively within this range will attract qualified buyers and reduce days on market.</li>
        </ul>
    </div>
    @endif

</div>

<div class="footer">
    <div class="footer-text">
        Generated {{ $report->created_at->format('F j, Y') }} &middot; Powered by PropOS &middot; Confidential
    </div>
    <div class="prep-block">
        <div class="prep-name">{{ $report->createdBy->name ?? 'PropOS Agent' }}</div>
        @if($report->createdBy?->email)
        <div class="prep-detail">{{ $report->createdBy->email }}</div>
        @endif
        @if($report->createdBy?->phone)
        <div class="prep-detail">{{ $report->createdBy->phone }}</div>
        @endif
    </div>
</div>

</body>
</html>
