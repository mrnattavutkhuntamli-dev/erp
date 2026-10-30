@extends('layouts.app')

@section('title', 'ใบเสร็จรับเงิน')

@section('content')
    @php
        $voided = $sale->status === 'voided';
        $customer = $sale->customer;
        $minRows = 8; // จำนวนแถวขั้นต่ำ เพื่อให้ตารางสูงเต็มหน้าเหมือนต้นแบบ
        $emptyRows = max(0, $minRows - $sale->items->count());
        $methodLabel = match ($sale->payment_method) {
            'cash' => 'เงินสด',
            'transfer' => 'โอนเงิน',
            default => 'บัตรเครดิต',
        };
    @endphp

    <style>
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff !important; }
            .receipt-sheet { box-shadow: none !important; margin: 0 !important; width: 210mm !important; min-height: 297mm !important; }
        }
        .receipt-sheet { width: 100%; max-width: 794px; min-height: 1000px; }
        .rc-table th { border: 1px solid #1f2937; background: #f3f4f6; padding: 6px 8px; font-size: 11px; font-weight: 600; text-align: center; }
        .rc-table td.col { border-left: 1px solid #1f2937; border-right: 1px solid #1f2937; padding: 8px; font-size: 12px; vertical-align: top; }
        .rc-table tr.last td.col { border-bottom: 1px solid #1f2937; }
        .rc-label { font-weight: 600; color: #111827; }
    </style>

    <div class="mx-auto max-w-[794px]">

        <!-- ปุ่มควบคุม (ซ่อนเวลาพิมพ์) -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('sales.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                กลับไปหน้าระบบขาย
            </a>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                พิมพ์ใบเสร็จ
            </button>
        </div>

        <!-- กระดาษใบเสร็จ -->
        <section class="receipt-sheet relative mx-auto flex flex-col bg-white px-10 py-10 text-slate-900 shadow-md ring-1 ring-slate-200 print:ring-0">

            @if ($voided)
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <span class="-rotate-12 rounded-xl border-4 border-red-500/60 px-8 py-2 text-6xl font-bold text-red-500/40">ยกเลิกแล้ว</span>
                </div>
            @endif

            <!-- หัวเอกสาร -->
            <header class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold leading-tight">ใบเสร็จรับเงิน</h1>
                    <p class="text-lg text-slate-600">Receipt</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-md bg-slate-700 text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8M14 7h7v7"/></svg>
                    </div>
                    <div class="leading-tight">
                        <p class="text-lg font-bold tracking-wide">AUTOPART</p>
                        <p class="text-lg font-bold tracking-wide">ERP CO.</p>
                    </div>
                </div>
            </header>

            <!-- ลูกค้า + เลขที่/วันที่ -->
            <div class="mt-6 flex justify-between gap-6 text-sm">
                <div class="space-y-1">
                    <p><span class="rc-label">ลูกค้า:</span> {{ $customer->name ?? 'ลูกค้าทั่วไป' }}</p>
                    <p><span class="rc-label">ที่อยู่:</span> {{ $customer->address ?? '-' }}</p>
                    @if ($customer && $customer->license_plate)
                        <p><span class="rc-label">ทะเบียนรถ:</span> {{ $customer->license_plate }}</p>
                    @endif
                </div>
                <div class="space-y-1 text-right sm:min-w-[200px]">
                    <p class="flex justify-between gap-6"><span class="rc-label">เลขที่:</span> <span>{{ $sale->invoice_no }}</span></p>
                    <p class="flex justify-between gap-6"><span class="rc-label">วันที่:</span> <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></p>
                </div>
            </div>

            <div class="my-4 border-t border-dotted border-slate-400"></div>

            <!-- ผู้ออกใบเสร็จ -->
            <div class="flex justify-between gap-6 text-sm">
                <div class="space-y-1">
                    <p><span class="rc-label">ผู้ออก:</span> AutoPart ERP</p>
                    <p><span class="rc-label">ที่อยู่:</span> 123 ถนนสุขุมวิท กรุงเทพฯ</p>
                </div>
                <div class="space-y-1 text-right sm:min-w-[240px]">
                    <p class="flex justify-between gap-6"><span class="rc-label">โทร:</span> <span>02-123-4567</span></p>
                    <p class="flex justify-between gap-6"><span class="rc-label">เลขประจำตัวผู้เสียภาษี:</span> <span>1234567890123</span></p>
                </div>
            </div>

            <!-- ตารางรายการ -->
            <div class="mt-5 overflow-x-auto">
                <table class="rc-table w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-14">ลำดับ</th>
                            <th>รายละเอียด</th>
                            <th class="w-16">จำนวน</th>
                            <th class="w-24">ราคา<br>ต่อหน่วย</th>
                            <th class="w-20">ส่วนลด</th>
                            <th class="w-28">รวมเป็นเงิน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $i => $item)
                            <tr>
                                <td class="col text-center">{{ $i + 1 }}</td>
                                <td class="col text-center">{{ $item->product->name }}</td>
                                <td class="col text-center">{{ $item->quantity }}</td>
                                <td class="col text-right">{{ number_format($item->price, 2) }}</td>
                                <td class="col text-center">-</td>
                                <td class="col text-right">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                        @for ($r = 0; $r < $emptyRows; $r++)
                            <tr>
                                @for ($c = 0; $c < 6; $c++)
                                    <td class="col">&nbsp;</td>
                                @endfor
                            </tr>
                        @endfor
                        <tr class="last">
                            @for ($c = 0; $c < 6; $c++)
                                <td class="col" style="padding:0;height:0"></td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- หมายเหตุ + สรุปยอด -->
            <div class="mt-3 flex items-start justify-between gap-6">
                <div class="text-xs">
                    <span class="rc-label">หมายเหตุ</span>
                    <p class="mt-1 text-slate-600">สินค้าซื้อแล้วไม่รับคืน/เปลี่ยน (ยกเว้นกรณีชำรุดจากผู้ผลิต)</p>
                </div>

                <table class="w-[300px] shrink-0 border-collapse text-xs">
                    <tr>
                        <td class="border border-slate-800 bg-slate-100 px-3 py-1.5 text-right font-semibold">ราคารวมสินค้า (บาท)</td>
                        <td class="w-24 border border-slate-800 px-3 py-1.5 text-right">{{ number_format($sale->subtotal, 2) }}-</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-800 bg-slate-100 px-3 py-1.5 text-right font-semibold">ส่วนลด</td>
                        <td class="border border-slate-800 px-3 py-1.5 text-right">{{ number_format($sale->discount, 2) }}-</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-800 bg-slate-100 px-3 py-2 text-right text-sm font-bold">จำนวนเงินรวมทั้งสิ้น (บาท)</td>
                        <td class="border border-slate-800 px-3 py-2 text-right text-sm font-bold">{{ number_format($sale->total, 2) }}-</td>
                    </tr>
                </table>
            </div>

            <!-- ข้อมูลการชำระเงิน + ลายเซ็น -->
            <div class="mt-auto pt-8">
                <div class="grid grid-cols-1 gap-6 text-xs sm:grid-cols-[1.3fr_1fr_1fr]">

                    <div class="space-y-2">
                        <p class="text-sm font-bold">ข้อมูลการชำระเงิน</p>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" disabled {{ $sale->payment_method == 'cash' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-400 text-slate-900">
                            <span class="font-semibold">เงินสด</span>
                            @if ($sale->payment_method == 'cash')
                                <span class="text-slate-600">รับเงินมา ฿{{ number_format($sale->received_amount, 2) }} / ทอน ฿{{ number_format($sale->change_amount, 2) }}</span>
                            @endif
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" disabled {{ $sale->payment_method == 'transfer' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-400 text-slate-900">
                            <span class="font-semibold">โอนเงิน</span>
                            @if ($sale->payment_method == 'transfer')
                                <span class="text-slate-600">฿{{ number_format($sale->received_amount, 2) }}</span>
                            @endif
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" disabled {{ !in_array($sale->payment_method, ['cash', 'transfer']) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-400 text-slate-900">
                            <span class="font-semibold">บัตรเครดิต</span>
                            @if (!in_array($sale->payment_method, ['cash', 'transfer']))
                                <span class="text-slate-600">฿{{ number_format($sale->received_amount, 2) }}</span>
                            @endif
                        </label>
                    </div>

                    <div class="flex flex-col items-center justify-end">
                        <div class="w-full border-b border-dotted border-slate-500"></div>
                        <p class="mt-2 font-medium">ผู้รับเงิน</p>
                        <p class="text-slate-500">วันที่ {{ $sale->created_at->format('d/m/Y') }}</p>
                    </div>

                    <div class="flex flex-col items-center justify-end">
                        <div class="w-full border-b border-dotted border-slate-500"></div>
                        <p class="mt-2 font-medium">ผู้ชำระเงิน</p>
                        <p class="text-slate-500">วันที่ ......................</p>
                    </div>
                </div>

                <p class="mt-6 text-center text-xs text-slate-500">ขอบคุณที่ใช้บริการ</p>
            </div>
        </section>
    </div>
@endsection
