<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสนอราคา {{ $quotation->quote_no }}</title>
    @php
        $minRows = 10; // จำนวนแถวขั้นต่ำ ให้ตารางสูงเต็มหน้า
        $emptyRows = max(0, $minRows - $quotation->items->count());
    @endphp
    <style>
        @page { margin: 28px 36px; }
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
        body { font-family: 'Sarabun', sans-serif; font-size: 12px; color: #1f2937; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: 700; }
        .muted { color: #6b7280; }

        /* หัวเอกสาร */
        .title { font-size: 24px; font-weight: 700; margin: 0; line-height: 1.1; }
        .subtitle { font-size: 17px; color: #4b5563; margin: 0; }
        .logo-box { display: inline-block; width: 40px; height: 40px; background: #374151; color: #fff; text-align: center; font-size: 22px; line-height: 40px; font-weight: 700; }
        .brand { font-size: 16px; font-weight: 700; line-height: 1.1; }

        /* ข้อมูลเอกสาร */
        .info td { padding: 2px 0; font-size: 12px; }
        .divider { border-top: 1px dotted #9ca3af; margin: 10px 0; height: 0; }

        /* ตารางรายการ */
        .items th { border: 1px solid #1f2937; background: #f3f4f6; padding: 6px 6px; font-size: 11px; font-weight: 700; text-align: center; }
        .items td { padding: 6px 8px; font-size: 12px; border-left: 1px solid #1f2937; border-right: 1px solid #1f2937; height: 22px; }
        .items tr.last td { border-bottom: 1px solid #1f2937; padding: 0; height: 0; }

        /* สรุปยอด */
        .sum td { border: 1px solid #1f2937; padding: 5px 10px; font-size: 11px; }
        .sum td.label { background: #f3f4f6; text-align: right; font-weight: 700; }
        .sum td.value { text-align: right; width: 95px; }
        .sum tr.grand td { font-size: 13px; font-weight: 700; padding: 7px 10px; }

        /* ลายเซ็น */
        .sign-line { border-top: 1px dotted #6b7280; margin: 0 24px; padding-top: 5px; font-weight: 700; }
    </style>
</head>
<body>

    <!-- หัวเอกสาร -->
    <table>
        <tr>
            <td>
                <p class="title">ใบเสนอราคา</p>
                <p class="subtitle">Quotation</p>
            </td>
            <td class="text-right">
                <table style="width:auto; margin-left:auto;">
                    <tr>
                        <td style="padding-right:8px;"><span class="logo-box">A</span></td>
                        <td style="text-align:left;">
                            <div class="brand">AUTOPART</div>
                            <div class="brand">ERP CO.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ลูกค้า + เลขที่/วันที่ -->
    <table class="info" style="margin-top:14px;">
        <tr>
            <td style="width:60%;"><span class="bold">ลูกค้า:</span> {{ $quotation->customer->name ?? 'ลูกค้าทั่วไป' }}</td>
            <td style="width:15%;" class="bold">เลขที่:</td>
            <td class="text-right">{{ $quotation->quote_no }}</td>
        </tr>
        <tr>
            <td><span class="bold">ที่อยู่:</span> {{ $quotation->customer->address ?? '-' }}</td>
            <td class="bold">วันที่:</td>
            <td class="text-right">{{ $quotation->quote_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>
                @if (optional($quotation->customer)->license_plate)
                    <span class="bold">ทะเบียนรถ:</span> {{ $quotation->customer->license_plate }}
                @endif
            </td>
            <td class="bold">ใช้ได้ถึง:</td>
            <td class="text-right">{{ $quotation->valid_until->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- ผู้เสนอราคา -->
    <table class="info">
        <tr>
            <td style="width:60%;"><span class="bold">ผู้เสนอราคา:</span> AutoPart ERP - ร้านขายอะไหล่รถยนต์ครบวงจร</td>
            <td style="width:15%;" class="bold">โทร:</td>
            <td class="text-right">02-123-4567</td>
        </tr>
        <tr>
            <td><span class="bold">ที่อยู่:</span> 123 ถนนสุขุมวิท กรุงเทพฯ</td>
            <td class="bold">เลขผู้เสียภาษี:</td>
            <td class="text-right">1234567890123</td>
        </tr>
    </table>

    <!-- ตารางรายการ -->
    <table class="items" style="margin-top:14px;">
        <thead>
            <tr>
                <th width="45">ลำดับ</th>
                <th>รายละเอียด</th>
                <th width="55">จำนวน</th>
                <th width="85">ราคา<br>ต่อหน่วย</th>
                <th width="95">รวมเป็นเงิน</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->name }} ({{ $item->product->sku }})</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
            @for ($r = 0; $r < $emptyRows; $r++)
                <tr>
                    <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                </tr>
            @endfor
            <tr class="last"><td></td><td></td><td></td><td></td><td></td></tr>
        </tbody>
    </table>

    <!-- หมายเหตุ + สรุปยอด -->
    <table style="margin-top:8px;">
        <tr>
            <td style="width:55%; padding-right:20px;">
                <div class="bold">หมายเหตุ</div>
                @if ($quotation->note)
                    <div class="muted" style="margin-top:3px;">{{ $quotation->note }}</div>
                @endif
                <div class="muted" style="margin-top:3px;">ใบเสนอราคานี้มีผลถึงวันที่ {{ $quotation->valid_until->format('d/m/Y') }}</div>
            </td>
            <td>
                <table class="sum">
                    <tr>
                        <td class="label">ราคารวมสินค้า (บาท)</td>
                        <td class="value">{{ number_format($quotation->subtotal, 2) }}-</td>
                    </tr>
                    <tr>
                        <td class="label">ส่วนลด</td>
                        <td class="value">{{ number_format($quotation->discount, 2) }}-</td>
                    </tr>
                    <tr class="grand">
                        <td class="label">จำนวนเงินรวมทั้งสิ้น (บาท)</td>
                        <td class="value">{{ number_format($quotation->total, 2) }}-</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ลายเซ็น -->
    <table style="margin-top:70px;">
        <tr>
            <td class="text-center" style="width:50%;">
                <div class="sign-line">ผู้เสนอราคา</div>
                <div class="muted" style="margin-top:3px;">วันที่ {{ $quotation->quote_date->format('d/m/Y') }}</div>
            </td>
            <td class="text-center" style="width:50%;">
                <div class="sign-line">ผู้อนุมัติ / ลูกค้า</div>
                <div class="muted" style="margin-top:3px;">วันที่ ......................</div>
            </td>
        </tr>
    </table>

</body>
</html>
