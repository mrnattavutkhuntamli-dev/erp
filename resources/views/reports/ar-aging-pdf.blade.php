<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานลูกหนี้ (A/R Aging)</title>
    <style>
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: 400;
            src: url("{{ storage_path('fonts/Sarabun-Regular.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: 700;
            src: url("{{ storage_path('fonts/Sarabun-Bold.ttf') }}") format('truetype');
        }
        @page { margin: 28px 30px 40px 30px; }
        body { font-family: 'Sarabun', sans-serif; font-size: 11px; color: #000; }

        .head { width: 100%; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 16px; }
        .head td { border: none; padding: 0; vertical-align: bottom; }
        h1 { font-size: 20px; margin: 0; color: #000; }
        .sub { font-size: 10px; color: #555; }
        .total-label { font-size: 10px; color: #555; text-align: right; }
        .total-value { font-size: 20px; font-weight: bold; color: #000; text-align: right; }

        .summary { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 18px -6px; }
        .summary td { border: 1px solid #999; border-top: 3px solid #000; padding: 8px 10px; background: #fff; }
        .s-ok   { border-top-width: 1px !important; }
        .s-warn { border-top-width: 2px !important; }
        .s-bad  { border-top-width: 4px !important; }
        .s-all  { border: 2px solid #000 !important; background: #eee !important; }
        .s-label { font-size: 9px; color: #555; }
        .s-all .s-label { color: #333; }
        .s-value { font-size: 15px; font-weight: bold; }
        .s-pct { font-size: 9px; color: #777; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #e5e5e5; color: #000; border-top: 1px solid #000; border-bottom: 1px solid #000; font-size: 10px; padding: 6px 8px; text-align: left; font-weight: bold; }
        table.data td { padding: 5px 8px; border-bottom: 1px solid #ccc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        tr.customer td { background: #f0f0f0; font-weight: bold; padding: 7px 8px; border-top: 6px solid #fff; border-bottom: 1px solid #999; }
        tr.customer .muted { font-weight: normal; color: #333; }
        tr.zebra td { background: #fafafa; }
        .paid { color: #000; }
        .due { font-weight: bold; color: #000; }
        .age-ok   { color: #000; }
        .age-warn { color: #000; font-weight: bold; }
        .age-bad  { color: #000; font-weight: bold; text-decoration: underline; }
        .foot { text-align: center; margin-top: 24px; color: #777; font-size: 9px; }
    </style>
</head>
<body>
    @php $grand = max((float) $totals['total'], 0.01); @endphp

    <table class="head">
        <tr>
            <td>
                <h1>รายงานลูกหนี้ (A/R Aging Report)</h1>
                <div class="sub">ข้อมูล ณ วันที่ {{ now()->format('d/m/Y H:i') }}</div>
            </td>
            <td style="width: 38%;">
                <div class="total-label">ยอดค้างชำระรวม</div>
                <div class="total-value">{{ number_format($totals['total'], 2) }} บาท</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td class="s-ok">
                <div class="s-label">ยังไม่ครบกำหนด (0-30 วัน)</div>
                <div class="s-value">{{ number_format($totals['current'], 2) }}</div>
                <div class="s-pct">{{ round($totals['current'] / $grand * 100, 1) }}% ของยอดค้าง</div>
            </td>
            <td class="s-warn">
                <div class="s-label">ค้าง 31-60 วัน</div>
                <div class="s-value">{{ number_format($totals['days_31_60'], 2) }}</div>
                <div class="s-pct">{{ round($totals['days_31_60'] / $grand * 100, 1) }}% ของยอดค้าง</div>
            </td>
            <td class="s-bad">
                <div class="s-label">ค้างเกิน 60 วัน</div>
                <div class="s-value">{{ number_format($totals['days_60_plus'], 2) }}</div>
                <div class="s-pct">{{ round($totals['days_60_plus'] / $grand * 100, 1) }}% ของยอดค้าง</div>
            </td>
            <td class="s-all">
                <div class="s-label">ยอดค้างชำระรวม</div>
                <div class="s-value">{{ number_format($totals['total'], 2) }}</div>
                <div class="s-label">บาท</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 18%;">เลขที่บิล</th>
                <th style="width: 14%;">วันที่ขาย</th>
                <th class="text-right">ยอดรวม</th>
                <th class="text-right">ชำระแล้ว</th>
                <th class="text-right">ค้างชำระ</th>
                <th class="text-center" style="width: 12%;">อายุหนี้</th>
            </tr>
        </thead>
        <tbody>
            @foreach($agingData as $customerId => $customer)
                <tr class="customer">
                    <td colspan="4">{{ $customer['name'] }} <span class="muted">(โทร: {{ $customer['phone'] }})</span></td>
                    <td colspan="2" class="text-right">ค้างรวม {{ number_format($customer['total'], 2) }} บาท</td>
                </tr>
                @foreach($customer['invoices'] as $inv)
                    <tr class="{{ $loop->even ? 'zebra' : '' }}">
                        <td>{{ $inv['invoice_no'] }}</td>
                        <td>{{ $inv['date'] }}</td>
                        <td class="text-right">{{ number_format($inv['total'], 2) }}</td>
                        <td class="text-right paid">{{ number_format($inv['paid'], 2) }}</td>
                        <td class="text-right due">{{ number_format($inv['due'], 2) }}</td>
                        <td class="text-center {{ $inv['days'] <= 30 ? 'age-ok' : ($inv['days'] <= 60 ? 'age-warn' : 'age-bad') }}">{{ $inv['days'] }} วัน</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="foot">รายงานนี้สร้างโดยระบบ AutoPart ERP</div>
</body>
</html>
