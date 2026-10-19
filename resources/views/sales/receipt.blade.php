@extends('layouts.app')

@section('title', 'ใบเสร็จรับเงิน')

@section('content')
    <div class="mx-auto max-w-2xl">

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

        @php $voided = $sale->status === 'voided'; @endphp

        <!-- ใบเสร็จ -->
        <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 print:rounded-none print:ring-0">

            <!-- หัวใบเสร็จ -->
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-6 py-5">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">AutoPart ERP</h1>
                    <p class="text-sm text-slate-500">123 ถนนสุขุมวิท กรุงเทพฯ | โทร: 02-123-4567</p>
                    <p class="text-sm text-slate-500">เลขประจำตัวผู้เสียภาษี: 1234567890123</p>
                </div>
                @if ($voided)
                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">ยกเลิกแล้ว</span>
                @endif
            </div>

            <!-- ข้อมูลใบเสร็จ -->
            <div class="grid gap-4 border-b border-slate-200 bg-slate-50 px-6 py-4 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-slate-500">เลขที่ใบเสร็จ</p>
                    <p class="font-medium text-slate-900">{{ $sale->invoice_no }}</p>
                </div>
                <div>
                    <p class="text-slate-500">วันที่</p>
                    <p class="font-medium text-slate-900">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">ลูกค้า</p>
                    <p class="font-medium text-slate-900">{{ $sale->customer->name ?? 'ลูกค้าทั่วไป' }}</p>
                    @if ($sale->customer && $sale->customer->license_plate)
                        <p class="mt-0.5 text-xs text-sky-700">ทะเบียนรถ: {{ $sale->customer->license_plate }}</p>
                    @endif
                </div>
            </div>

            <!-- รายการสินค้า -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">รายการ</th>
                            <th class="px-6 py-3 font-medium text-center">จำนวน</th>
                            <th class="px-6 py-3 font-medium text-right">ราคา</th>
                            <th class="px-6 py-3 font-medium text-right">รวม</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="px-6 py-3 font-medium text-slate-900">{{ $item->product->name }}</td>
                                <td class="px-6 py-3 text-center text-slate-500">{{ $item->quantity }}</td>
                                <td class="px-6 py-3 text-right text-slate-500">฿{{ number_format($item->price, 2) }}</td>
                                <td class="px-6 py-3 text-right font-medium text-slate-900">฿{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- สรุปยอด -->
            <div class="space-y-1.5 border-t border-slate-200 px-6 py-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">ยอดรวม</span>
                    <span class="text-slate-900">฿{{ number_format($sale->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">ส่วนลด</span>
                    <span class="text-slate-900">-฿{{ number_format($sale->discount, 2) }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-3 mt-2">
                    <span class="font-semibold text-slate-900">ยอดสุทธิ</span>
                    <span class="text-xl font-semibold text-slate-900">฿{{ number_format($sale->total, 2) }}</span>
                </div>
            </div>

            <!-- การชำระเงิน -->
            <div class="space-y-1.5 border-t border-slate-200 bg-slate-50 px-6 py-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">วิธีชำระ</span>
                    @if ($sale->payment_method == 'cash')
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">เงินสด</span>
                    @elseif ($sale->payment_method == 'transfer')
                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium text-sky-700">โอนเงิน</span>
                    @else
                        <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-700">บัตรเครดิต</span>
                    @endif
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">รับเงินมา</span>
                    <span class="text-slate-900">฿{{ number_format($sale->received_amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">เงินทอน</span>
                    <span class="font-medium text-slate-900">฿{{ number_format($sale->change_amount, 2) }}</span>
                </div>
            </div>

            <!-- ท้ายใบเสร็จ -->
            <div class="border-t border-slate-200 px-6 py-5 text-center text-xs text-slate-500">
                <p class="text-sm font-medium text-slate-700">ขอบคุณที่ใช้บริการ</p>
                <p class="mt-1">สินค้าซื้อแล้วไม่รับคืน/เปลี่ยน (ยกเว้นกรณีชำรุดจากผู้ผลิต)</p>
            </div>
        </section>
    </div>
@endsection
