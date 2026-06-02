<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 0; }
  .header { background: #6366f1; color: white; padding: 24px 32px; }
  .header h1 { margin: 0; font-size: 20px; font-weight: bold; }
  .header p  { margin: 4px 0 0; font-size: 11px; opacity: 0.85; }
  .content { padding: 24px 32px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th { background: #f8fafc; text-align: left; padding: 7px 10px; font-size: 10px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; }
  td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; }
  .total-box { background: #f0f4ff; border: 1px solid #818cf8; border-radius: 6px; padding: 14px 20px; margin-top: 16px; }
  .total-box .label { font-size: 11px; color: #4f46e5; font-weight: bold; }
  .total-box .value { font-size: 22px; font-weight: bold; color: #312e81; margin-top: 4px; }
  .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
  .badge-paid { background: #dcfce7; color: #15803d; }
  .badge-approved { background: #dbeafe; color: #1d4ed8; }
</style>
</head>
<body>
<div class="header">
  <h1>Tax Deductible Expenses</h1>
  <p>Period: {{ \Carbon\Carbon::create(null, $month, 1)->format('F') }} {{ $year }}
    @if($property) &nbsp;|&nbsp; {{ $property->address_line_1 }}, {{ $property->city }} @endif
  </p>
</div>
<div class="content">
  <table>
    <thead>
      <tr>
        <th>Reference</th>
        <th>Category</th>
        <th>Property</th>
        <th>Date</th>
        <th style="text-align:right">Amount</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($taxItems as $expense)
      <tr>
        <td>{{ $expense->reference ?? '—' }}</td>
        <td style="text-transform:capitalize">{{ str_replace('_',' ',$expense->category) }}</td>
        <td>{{ $expense->property?->address_line_1 ?? '—' }}</td>
        <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
        <td style="text-align:right">R{{ number_format($expense->amount) }}</td>
        <td><span class="badge badge-{{ $expense->status }}">{{ $expense->status }}</span></td>
      </tr>
      @empty
      <tr><td colspan="6" style="color:#94a3b8; text-align:center; padding: 20px;">No tax deductible expenses found for this period.</td></tr>
      @endforelse
    </tbody>
  </table>
  @if($taxItems->isNotEmpty())
  <div class="total-box">
    <div class="label">Total Tax Deductible Amount</div>
    <div class="value">R{{ number_format($taxTotal) }}</div>
  </div>
  @endif
  <div class="footer">
    <span>Generated {{ now()->format('d M Y H:i') }}</span>
    <span>Confidential — for internal use only</span>
  </div>
</div>
</body>
</html>
