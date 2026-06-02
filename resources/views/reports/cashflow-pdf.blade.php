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
  table { width: 100%; border-collapse: collapse; }
  th { background: #f8fafc; text-align: right; padding: 7px 10px; font-size: 10px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; }
  th:first-child { text-align: left; }
  td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; text-align: right; }
  td:first-child { text-align: left; font-weight: 600; }
  .positive { color: #16a34a; }
  .negative { color: #dc2626; }
  .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="header">
  <h1>Cash Flow Statement</h1>
  <p>Year: {{ $year }}
    @if($property) &nbsp;|&nbsp; {{ $property->address_line_1 }}, {{ $property->city }} @endif
  </p>
</div>
<div class="content">
  <table>
    <thead>
      <tr>
        <th>Month</th>
        <th>Income</th>
        <th>Expenses</th>
        <th>Net</th>
        <th>Outstanding AR</th>
      </tr>
    </thead>
    <tbody>
      @php $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
      @foreach($cashFlowData as $row)
      <tr>
        <td>{{ $months[$row['month'] - 1] }}</td>
        <td class="positive">R{{ number_format($row['income']) }}</td>
        <td class="negative">R{{ number_format($row['expenses']) }}</td>
        <td class="{{ $row['net'] >= 0 ? 'positive' : 'negative' }}">R{{ number_format($row['net']) }}</td>
        <td>R{{ number_format($row['outstanding_ar']) }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr style="font-weight:bold; background:#f8fafc; border-top: 2px solid #e2e8f0;">
        <td>Total</td>
        <td class="positive">R{{ number_format(collect($cashFlowData)->sum('income')) }}</td>
        <td class="negative">R{{ number_format(collect($cashFlowData)->sum('expenses')) }}</td>
        @php $netTotal = collect($cashFlowData)->sum('net'); @endphp
        <td class="{{ $netTotal >= 0 ? 'positive' : 'negative' }}">R{{ number_format($netTotal) }}</td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  <div class="footer">
    <span>Generated {{ now()->format('d M Y H:i') }}</span>
    <span>Confidential — for internal use only</span>
  </div>
</div>
</body>
</html>
