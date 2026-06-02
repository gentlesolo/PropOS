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
  th { background: #f8fafc; text-align: left; padding: 7px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
  td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
  .total-row td { font-weight: bold; border-top: 2px solid #e2e8f0; background: #f8fafc; }
  .bar-wrap { background: #f1f5f9; border-radius: 3px; height: 8px; }
  .bar-fill { background: #6366f1; border-radius: 3px; height: 8px; }
  .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="header">
  <h1>Accounts Receivable Aging Report</h1>
  <p>As at {{ now()->format('d M Y') }}</p>
</div>
<div class="content">
  <table>
    <thead>
      <tr>
        <th>Aging Bucket</th>
        <th style="text-align:right">Balance</th>
        <th style="text-align:right">% of Total</th>
        <th style="width:200px">Distribution</th>
      </tr>
    </thead>
    <tbody>
      @foreach($agingBuckets as $bucket => $amount)
      @php $pct = $agingTotal > 0 ? round(($amount / $agingTotal) * 100) : 0; @endphp
      <tr>
        <td>{{ $bucket }}</td>
        <td style="text-align:right">R{{ number_format($amount) }}</td>
        <td style="text-align:right">{{ $pct }}%</td>
        <td>
          <div class="bar-wrap">
            <div class="bar-fill" style="width:{{ $pct }}%"></div>
          </div>
        </td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td>Total Outstanding</td>
        <td style="text-align:right">R{{ number_format($agingTotal) }}</td>
        <td style="text-align:right">100%</td>
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
