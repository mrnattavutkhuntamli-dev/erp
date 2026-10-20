<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายงานยอดขาย</title>
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

        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 12px;
            color: #333;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 16px;
            margin-top: 20px;
            margin-bottom: 10px;
            background: #f0f0f0;
            padding: 8px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .info {
            margin-bottom: 15px;
        }

        .info span {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background: #333;
            color: white;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-item {
            display: table-cell;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .summary-item label {
            display: block;
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-item value {
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
        }

        .text-red {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <!-- ✅ ลบอีโมจิออก -->
        <h1>AutoPart ERP - รายงานยอดขาย</h1>
        <p>ช่วงวันที่: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} ถึง
            {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
        <p>พิมพ์เมื่อ: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <!-- สรุปภาพรวม -->
    <div class="summary">
        <div class="summary-item">
            <label>ยอดขายรวม</label>
            <!-- ✅ เปลี่ยน ฿ เป็นคำว่า บาท -->
            <value>{{ number_format($totalSales, 2) }} บาท</value>
        </div>
        <div class="summary-item">
            <label>จำนวนบิล</label>
            <value>{{ number_format($totalBills) }} บิล</value>
        </div>
        <div class="summary-item">
            <label>กำไรขั้นต้น</label>
            <value>{{ number_format($grossProfit, 2) }} บาท</value>
        </div>
    </div>

    <!-- สินค้าขายดี -->
    <!-- ✅ ลบอีโมจิออก -->
    <h2>สินค้าขายดี 10 อันดับ</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center" width="40">อันดับ</th>
                <th>รหัสสินค้า</th>
                <th>ชื่อสินค้า</th>
                <th class="text-center">จำนวนที่ขาย</th>
                <th class="text-right">รายได้รวม</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topProducts as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->sku ?? '-' }}</td>
                    <td>{{ $item->product->name ?? 'สินค้าถูกลบ' }}</td>
                    <td class="text-center">{{ number_format($item->total_qty) }}</td>
                    <!-- ✅ เปลี่ยน ฿ เป็น บาท -->
                    <td class="text-right">{{ number_format($item->total_revenue, 2) }} บาท</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">ไม่มีข้อมูลการขายในช่วงนี้</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- รายงานสต็อก -->
    <!-- ✅ ลบอีโมจิออก -->
    <h2>รายงานสินค้าคงเหลือ (Stock Card)</h2>
    <table>
        <thead>
            <tr>
                <th>รหัสสินค้า</th>
                <th>ชื่อสินค้า</th>
                <th>หมวดหมู่</th>
                <th>ยี่ห้อ</th>
                <th class="text-center">สต็อกคงเหลือ</th>
                <th class="text-right">มูลค่าสต็อก (ราคาทุน)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stockReport as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td>{{ $product->brand->name ?? '-' }}</td>
                    <td class="text-center {{ $product->stock < 10 ? 'text-red' : '' }}">
                        {{ number_format($product->stock) }}
                    </td>
                    <!-- ✅ เปลี่ยน ฿ เป็น บาท -->
                    <td class="text-right">{{ number_format($product->cost * $product->stock, 2) }} บาท</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: center; color: #999; font-size: 10px;">
        <p>รายงานนี้สร้างโดยระบบ AutoPart ERP</p>
    </div>
</body>

</html>
