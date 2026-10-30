@extends('layouts.app')
@section('title', 'รายละเอียดใบรับเงิน')

@section('content')
    <style>
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff !important; }
            .doc-sheet { box-shadow: none !important; margin: 0 !important; width: 210mm !important; min-height: 297mm !important; }
        }
        .doc-sheet { width: 100%; max-width: 794px; min-height: 1000px; }
        .doc-table th { border: 1px solid #1f2937; background: #f3f4f6; padding: 6px 8px; font-size: 11px; font-weight: 600; text-align: center; }
        .doc-table td.col { border-left: 1px solid #1f2937; border-right: 1px solid #1f2937; padding: 8px; font-size: 12px; vertical-align: top; }
        .doc-table tr.last td.col { border-bottom: 1px solid #1f2937; padding: 0; }
        .doc-label { font-weight: 600; color: #111827; }
    </style>

    <div class="mx-auto max-w-[794px]">
        <div class="mb-4 flex items-center justify-between print:hidden">
            <a href="{{ route('receipts.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                กลับ
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-emerald-100 p-4 text-sm text-emerald-700 print:hidden">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-100 p-4 text-sm text-red-700 print:hidden">{{ session('error') }}</div>
        @endif

        {{-- ============================================ --}}
        {{-- กรณีที่ 1: แสดงฟอร์มรับชำระเงินใหม่ (มี $sale) --}}
        {{-- ============================================ --}}
        @if(isset($sale) && $sale)
            @php $remaining = $sale->total - $sale->paid_amount; @endphp

            <div class="mx-auto max-w-xl overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h1 class="text-xl font-bold text-slate-900">บันทึกรับชำระเงิน</h1>
                    <p class="text-sm text-slate-500">Payment Receipt</p>
                </div>

                <!-- สรุปบิล -->
                <div class="space-y-1.5 border-b border-slate-200 bg-slate-50 px-6 py-4 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">บิลที่เลือก</span><span class="font-medium text-slate-900">{{ $sale->invoice_no }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">ลูกค้า</span><span class="font-medium text-slate-900">{{ $sale->customer->name ?? 'ลูกค้าทั่วไป' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">ยอดรวม</span><span class="text-slate-900">฿{{ number_format($sale->total, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">จ่ายแล้ว</span><span class="text-slate-900">฿{{ number_format($sale->paid_amount, 2) }}</span></div>
                    <div class="flex items-center justify-between border-t border-slate-200 pt-2 mt-1">
                        <span class="font-semibold text-slate-900">ค้างชำระ</span>
                        <span class="text-lg font-semibold text-red-600">฿{{ number_format($remaining, 2) }}</span>
                    </div>
                </div>

                <form action="{{ route('receipts.store') }}" method="POST" class="space-y-4 px-6 py-5">
                    @csrf
                    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">วันที่รับชำระ <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-lg border-slate-300 p-2 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">จำนวนเงินที่รับ <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="amount" value="{{ $remaining }}" required min="0.01" max="{{ $remaining }}"
                               class="w-full rounded-lg border-slate-300 p-2 text-right text-lg font-semibold focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">วิธีชำระ <span class="text-red-500">*</span></label>
                        <select name="payment_method" required
                                class="w-full rounded-lg border-slate-300 p-2 text-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="cash">เงินสด</option>
                            <option value="transfer">โอนเงิน</option>
                            <option value="credit_card">บัตรเครดิต</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">หมายเหตุ</label>
                        <textarea name="note" rows="2"
                                  class="w-full rounded-lg border-slate-300 p-2 text-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
                    </div>

                    <div class="flex justify-end border-t border-slate-200 pt-4">
                        <button type="submit"
                                class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition-colors">
                            บันทึกการรับเงิน
                        </button>
                    </div>
                </form>
            </div>

        {{-- ============================================ --}}
        {{-- กรณีที่ 2: แสดงรายละเอียด Receipt ที่มีอยู่แล้ว --}}
        {{-- ============================================ --}}
        @elseif(isset($receipt) && $receipt->exists)
            @php
                $voided = $receipt->status === 'voided';
                $sale = $receipt->sale;
                $customer = $sale->customer ?? null;
                $emptyRows = 8;
                $method = $receipt->payment_method;
                $methodLabel = match ($method) {
                    'cash' => 'เงินสด',
                    'transfer' => 'โอนเงิน',
                    default => 'บัตรเครดิต',
                };
            @endphp

            <section class="doc-sheet relative mx-auto flex flex-col bg-white px-10 py-10 text-slate-900 shadow-md ring-1 ring-slate-200 print:ring-0">

                @if ($voided)
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <span class="-rotate-12 rounded-xl border-4 border-red-500/60 px-8 py-2 text-6xl font-bold text-red-500/40">ยกเลิกแล้ว</span>
                    </div>
                @endif

                <!-- หัวเอกสาร -->
                <header class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold leading-tight">ใบรับชำระเงิน</h1>
                        <p class="text-lg text-slate-600">Payment Receipt</p>
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
                        <p><span class="doc-label">ลูกค้า:</span> {{ $customer->name ?? 'ลูกค้าทั่วไป' }}</p>
                        <p><span class="doc-label">ที่อยู่:</span> {{ $customer->address ?? '-' }}</p>
                        @if ($customer && $customer->license_plate)
                            <p><span class="doc-label">ทะเบียนรถ:</span> {{ $customer->license_plate }}</p>
                        @endif
                    </div>
                    <div class="space-y-1 sm:min-w-[210px]">
                        <p class="flex justify-between gap-6"><span class="doc-label">เลขที่:</span> <span>{{ $receipt->receipt_no }}</span></p>
                        <p class="flex justify-between gap-6"><span class="doc-label">วันที่รับชำระ:</span> <span>{{ $receipt->payment_date->format('d/m/Y') }}</span></p>
                        <p class="flex justify-between gap-6"><span class="doc-label">อ้างอิงบิล:</span> <span>{{ $sale->invoice_no }}</span></p>
                    </div>
                </div>

                <div class="my-4 border-t border-dotted border-slate-400"></div>

                <!-- ผู้รับเงิน -->
                <div class="flex justify-between gap-6 text-sm">
                    <div class="space-y-1">
                        <p><span class="doc-label">ผู้รับเงิน:</span> AutoPart ERP</p>
                        <p><span class="doc-label">ที่อยู่:</span> 123 ถนนสุขุมวิท กรุงเทพฯ</p>
                    </div>
                    <div class="space-y-1 sm:min-w-[210px]">
                        <p class="flex justify-between gap-6"><span class="doc-label">โทร:</span> <span>02-123-4567</span></p>
                        <p class="flex justify-between gap-6"><span class="doc-label">เลขผู้เสียภาษี:</span> <span>1234567890123</span></p>
                    </div>
                </div>

                <!-- ตารางรายการ -->
                <div class="mt-5 overflow-x-auto">
                    <table class="doc-table w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="w-14">ลำดับ</th>
                                <th>รายละเอียด</th>
                                <th class="w-32">ยอดบิล</th>
                                <th class="w-32">รับชำระครั้งนี้</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="col text-center">1</td>
                                <td class="col">
                                    <div>รับชำระเงินตามบิลเลขที่ {{ $sale->invoice_no }}</div>
                                    <div class="text-xs text-slate-500">ชำระโดย{{ $methodLabel }}</div>
                                </td>
                                <td class="col text-right">{{ number_format($sale->total, 2) }}</td>
                                <td class="col text-right">{{ number_format($receipt->amount, 2) }}</td>
                            </tr>
                            @for ($r = 0; $r < $emptyRows; $r++)
                                <tr>
                                    @for ($c = 0; $c < 4; $c++)
                                        <td class="col">&nbsp;</td>
                                    @endfor
                                </tr>
                            @endfor
                            <tr class="last">
                                @for ($c = 0; $c < 4; $c++)
                                    <td class="col"></td>
                                @endfor
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- หมายเหตุ + สรุปยอด -->
                <div class="mt-3 flex items-start justify-between gap-6">
                    <div class="text-xs">
                        <span class="doc-label">หมายเหตุ</span>
                        @if ($receipt->note)
                            <p class="mt-1 text-slate-600">{{ $receipt->note }}</p>
                        @endif
                    </div>

                    <table class="w-[300px] shrink-0 border-collapse text-xs">
                        <tr>
                            <td class="border border-slate-800 bg-slate-100 px-3 py-1.5 text-right font-semibold">ยอดบิลรวม (บาท)</td>
                            <td class="w-24 border border-slate-800 px-3 py-1.5 text-right">{{ number_format($sale->total, 2) }}-</td>
                        </tr>
                        <tr>
                            <td class="border border-slate-800 bg-slate-100 px-3 py-1.5 text-right font-semibold">ค้างชำระ (บาท)</td>
                            <td class="border border-slate-800 px-3 py-1.5 text-right">{{ number_format($sale->total - $sale->paid_amount, 2) }}-</td>
                        </tr>
                        <tr>
                            <td class="border border-slate-800 bg-slate-100 px-3 py-2 text-right text-sm font-bold">รับชำระครั้งนี้ (บาท)</td>
                            <td class="border border-slate-800 px-3 py-2 text-right text-sm font-bold">{{ number_format($receipt->amount, 2) }}-</td>
                        </tr>
                    </table>
                </div>

                <!-- ข้อมูลการชำระเงิน + ลายเซ็น -->
                <div class="mt-auto pt-8">
                    <div class="grid grid-cols-1 gap-6 text-xs sm:grid-cols-[1.3fr_1fr_1fr]">
                        <div class="space-y-2">
                            <p class="text-sm font-bold">ข้อมูลการชำระเงิน</p>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" disabled {{ $method === 'cash' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-400 text-slate-900">
                                <span class="font-semibold">เงินสด</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" disabled {{ $method === 'transfer' ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-400 text-slate-900">
                                <span class="font-semibold">โอนเงิน</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" disabled {{ !in_array($method, ['cash', 'transfer']) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-400 text-slate-900">
                                <span class="font-semibold">บัตรเครดิต</span>
                            </label>
                        </div>

                        <div class="flex flex-col items-center justify-end">
                            <div class="w-full border-b border-dotted border-slate-500"></div>
                            <p class="mt-2 font-medium">ผู้รับเงิน</p>
                            <p class="text-slate-500">{{ $receipt->user->name }}</p>
                            <p class="text-slate-500">วันที่ {{ $receipt->payment_date->format('d/m/Y') }}</p>
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

            @if ($voided)
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 print:hidden">
                    <p class="font-semibold text-red-700">ใบรับเงินนี้ถูกยกเลิกแล้ว</p>
                    <p class="mt-1 text-sm text-red-600">เหตุผล: {{ $receipt->void_reason }}</p>
                    <p class="text-sm text-red-600">ยกเลิกโดย: {{ $receipt->voidedBy->name ?? '-' }} เมื่อ {{ $receipt->voided_at->format('d/m/Y H:i') }}</p>
                </div>
            @else
                <div class="mt-4 flex items-center justify-between print:hidden">
                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                        พิมพ์ใบรับเงิน
                    </button>

                    @can('void-receipt')
                    <button type="button" onclick="openVoidModal()"
                            class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                        ยกเลิกใบรับเงิน
                    </button>
                    @endcan
                </div>

                <!-- Modal สำหรับกรอกเหตุผลการ Void -->
                <div id="voidModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 print:hidden">
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
                        <h3 class="mb-1 text-lg font-semibold text-slate-900">ยกเลิกใบรับเงิน</h3>
                        <p class="mb-4 text-sm text-slate-600">กรุณาระบุเหตุผลการยกเลิก (จำเป็น)</p>
                        <form action="{{ route('receipts.destroy', $receipt->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <textarea name="void_reason" required rows="3" placeholder="เช่น ลูกค้าโอนผิดบัญชี..."
                                      class="mb-4 w-full rounded-lg border-slate-300 p-2 text-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="closeVoidModal()" class="rounded-lg bg-slate-100 px-4 py-2 text-sm hover:bg-slate-200">ไม่ยกเลิก</button>
                                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">ยืนยันการยกเลิก</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    function openVoidModal() {
                        document.getElementById('voidModal').classList.remove('hidden');
                        document.getElementById('voidModal').classList.add('flex');
                    }
                    function closeVoidModal() {
                        document.getElementById('voidModal').classList.add('hidden');
                        document.getElementById('voidModal').classList.remove('flex');
                    }
                </script>
            @endif

        {{-- ============================================ --}}
        {{-- กรณีที่ 3: ไม่พบข้อมูล --}}
        {{-- ============================================ --}}
        @else
            <div class="rounded-lg bg-red-100 p-4 text-red-700">ไม่พบข้อมูลใบรับเงิน หรือบิลที่ระบุ</div>
        @endif
    </div>
@endsection
