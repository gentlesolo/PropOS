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
  h2 { font-size: 13px; font-weight: bold; color: #1e293b; margin: 0 0 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th { background: #f8fafc; text-align: left; padding: 7px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
  td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; }
  .total-row td { font-weight: bold; border-top: 2px solid #e2e8f0; }
  .net-box { background: #f0f4ff; border: 1px solid #818cf8; border-radius: 6px; padding: 14px 20px; margin-top: 16px; }
  .net-box .label { font-size: 11px; color: #4f46e5; font-weight: bold; }
  .net-box .value { font-size: 22px; font-weight: bold; color: #312e81; margin-top: 4px; }
  .positive { color: #16a34a; }
  .negative { color: #dc2626; }
  .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
</style>
</head>
<body>
<div class="header">
  <h1>Profit &amp; Loss Summary</h1>
  <p>Period: {{ \Carbon\Carbon::create(null, $month, 1)->format('F') }} {{ $year }}
    @if($property) &nbsp;|&nbsp; {{ $property->address_line_1 }}, {{ $property->city }} @endif
  </p>
</div>
<div class="content">
  <div class="grid-2">
    <div>
      <h2>Income</h2>
      <table>
        <thead><tr><th>Category</th><th style="text-align:right">Amount</th></tr></thead>
        <tbody>
          @foreach($incomeRows as $label => $amount)
          <tr><td>{{ $label }}</td><td style="text-align:right">R{{ number_format($amount) }}</td></tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr class="total-row"><td>Total Income</td><td style="text-align:right" class="positive">R{{ number_format($totalIncome) }}</td></tr>
        </tfoot>
      </table>
    </div>
    <div>
      <h2>Expenses</h2>
      <table>
        <thead><tr><th>Category</th><th style="text-align:right">Amount</th></tr></thead>
        <tbody>
          @forelse($expenseRows as $cat => $amount)
          <tr><td style="text-transform:capitalize">{{ str_replace('_',' ',$cat) }}</td><td style="text-align:right">R{{ number_format($amount) }}</td></tr>
          @empty
          <tr><td colspan="2" style="color:#94a3b8">No expenses recorded</td></tr>
          @endforelse
        </tbody>
        <tfoot>
          <tr class="total-row"><td>Total Expenses</td><td style="text-align:right" class="negative">R{{ number_format($totalExpenses) }}</td></tr>
        </tfoot>
      </table>
    </div>
  </div>
  @php $net = $totalIncome - $totalExpenses; @endphp
  <div class="net-box">
    <div class="label">Net Operating Income</div>
    <div class="value {{ $net >= 0 ? 'positive' : 'negative' }}">R{{ number_format(abs($net)) }} {{ $net < 0 ? '(Loss)' : '' }}</div>
  </div>
  <div class="footer">
    <span>Generated {{ now()->format('d M Y H:i') }}</span>
    <span>Confidential — for internal use only</span>
  </div>
</div>
</body>
</html>
