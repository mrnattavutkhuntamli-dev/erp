<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสนอราคา {{ $quotation->quote_no }}</title>
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
        body { font-family: 'Sarabun', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 20px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .doc-info { margin-bottom: 20px; }
        .doc-info table { border: none; }
        .doc-info td { border: none; padding: 2px 0; }
        .total-box { text-align: right; font-size: 14px; font-weight: bold; }
        .signature { margin-top: 60px; text-align: center; display: inline-block; width: 45%; }
        .signature-line { border-top: 1px dotted #333; margin: 0 30px; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AutoPart ERP - ใบเสนอราคา</h1>
        <p>ร้านขายอะไหล่รถยนต์ครบวงจร | 123 ถนนสุขุมวิท กรุงเทพฯ | โทร: 02-123-4567</p>
    </div>

    <div class="doc-info">
        <table>
            <tr>
                <td><strong>เลขที่:</strong> {{ $quotation->quote_no }}</td>
                <td class="text-right"><strong>วันที่:</strong> {{ $quotation->quote_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>ลูกค้า:</strong> {{ $quotation->customer->name ?? 'ลูกค้าทั่วไป' }}</td>
                <td class="text-right"><strong>ใช้ได้ถึง:</strong> {{ $quotation->valid_until->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="30">#</th>
                <th>รายการสินค้า</th>
                <th class="text-center" width="60">จำนวน</th>
                <th class="text-right" width="80">ราคา/หน่วย</th>
                <th class="text-right" width="80">รวม</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->name }} ({{ $item->product->sku }})</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <p>ยอดรวม: {{ number_format($quotation->subtotal, 2) }} บาท</p>
        <p>ส่วนลด: -{{ number_format($quotation->discount, 2) }} บาท</p>
        <p>ยอดสุทธิ: {{ number_format($quotation->total, 2) }} บาท</p>
    </div>

    @if($quotation->note)
        <div style="margin-top: 20px;">
            <strong>หมายเหตุ:</strong> {{ $quotation->note }}
        </div>
    @endif

    <div style="margin-top: 60px; text-align: center;">
        <div class="signature">
            <div class="signature-line">ผู้เสนอราคา</div>
        </div>
        <div class="signature">
            <div class="signature-line">ผู้อนุมัติ / ลูกค้า</div>
        </div>
    </div>
</body>
</html>
