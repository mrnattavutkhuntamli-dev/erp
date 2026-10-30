@extends('layouts.app')
@section('title', 'รายละเอียดใบเสนอราคา')

@section('content')
    @php
        $customer = $quotation->customer;
        $minRows = 8; // จำนวนแถวขั้นต่ำ ให้ตารางสูงเต็มหน้าเหมือนต้นแบบ
        $emptyRows = max(0, $minRows - $quotation->items->count());
        $statusMap = [
            'draft'    => ['ฉบับร่าง',  'bg-slate-100 text-slate-700'],
            'sent'     => ['ส่งแล้ว',    'bg-sky-100 text-sky-700'],
            'accepted' => ['อนุมัติแล้ว', 'bg-emerald-100 text-emerald-700'],
            'converted'=> ['แปลงเป็นใบเสร็จแล้ว', 'bg-emerald-100 text-emerald-700'],
            'rejected' => ['ปฏิเสธ',     'bg-red-100 text-red-700'],
        ];
        [$statusLabel, $statusClass] = $statusMap[$quotation->status] ?? [$quotation->status, 'bg-slate-100 text-slate-700'];
    @endphp

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

        <!-- แถบควบคุม (ซ่อนเวลาพิมพ์) -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('quotations.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                กลับไปหน้ารายการ
            </a>
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                    พิมพ์
                </button>
                <a href="{{ route('quotations.pdf', $quotation->id) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    ดาวน์โหลด PDF
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-emerald-100 p-4 text-sm text-emerald-700 print:hidden">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-100 p-4 text-sm text-red-700 print:hidden">{{ session('error') }}</div>
        @endif

        <!-- กระดาษใบเสนอราคา -->
        <section class="doc-sheet relative mx-auto flex flex-col bg-white px-10 py-10 text-slate-900 shadow-md ring-1 ring-slate-200 print:ring-0">

            @if ($quotation->status === 'rejected')
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <span class="-rotate-12 rounded-xl border-4 border-red-500/60 px-8 py-2 text-6xl font-bold text-red-500/40">ปฏิเสธ</span>
                </div>
            @endif

            <!-- หัวเอกสาร -->
            <header class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold leading-tight">ใบเสนอราคา</h1>
                    <p class="text-lg text-slate-600">Quotation</p>
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
                    @if ($customer && $customer->phone)
                        <p><span class="doc-label">เบอร์โทร:</span> {{ $customer->phone }}</p>
                    @endif
                    @if ($customer && $customer->license_plate)
                        <p><span class="doc-label">ทะเบียนรถ:</span> {{ $customer->license_plate }}</p>
                    @endif
                </div>
                <div class="space-y-1 sm:min-w-[210px]">
                    <p class="flex justify-between gap-6"><span class="doc-label">เลขที่:</span> <span>{{ $quotation->quote_no }}</span></p>
                    <p class="flex justify-between gap-6"><span class="doc-label">วันที่:</span> <span>{{ $quotation->quote_date->format('d/m/Y') }}</span></p>
                    <p class="flex justify-between gap-6"><span class="doc-label">ใช้ได้ถึง:</span> <span>{{ $quotation->valid_until->format('d/m/Y') }}</span></p>
                </div>
            </div>

            <div class="my-4 border-t border-dotted border-slate-400"></div>

            <!-- ผู้เสนอราคา -->
            <div class="flex justify-between gap-6 text-sm">
                <div class="space-y-1">
                    <p><span class="doc-label">ผู้เสนอราคา:</span> AutoPart ERP - ร้านขายอะไหล่รถยนต์ครบวงจร</p>
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
                            <th class="w-16">จำนวน</th>
                            <th class="w-24">ราคา<br>ต่อหน่วย</th>
                            <th class="w-28">รวมเป็นเงิน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotation->items as $index => $item)
                            <tr>
                                <td class="col text-center">{{ $index + 1 }}</td>
                                <td class="col">
                                    <div>{{ $item->product->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->product->sku }}</div>
                                </td>
                                <td class="col text-center">{{ $item->quantity }}</td>
                                <td class="col text-right">{{ number_format($item->price, 2) }}</td>
                                <td class="col text-right">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                        @for ($r = 0; $r < $emptyRows; $r++)
                            <tr>
                                @for ($c = 0; $c < 5; $c++)
                                    <td class="col">&nbsp;</td>
                                @endfor
                            </tr>
                        @endfor
                        <tr class="last">
                            @for ($c = 0; $c < 5; $c++)
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
                    @if ($quotation->note)
                        <p class="mt-1 text-slate-600">{{ $quotation->note }}</p>
                    @endif
                    <p class="mt-1 text-slate-600">ใบเสนอราคานี้มีผลถึงวันที่ {{ $quotation->valid_until->format('d/m/Y') }}</p>
                </div>

                <table class="w-[300px] shrink-0 border-collapse text-xs">
                    <tr>
                        <td class="border border-slate-800 bg-slate-100 px-3 py-1.5 text-right font-semibold">ราคารวมสินค้า (บาท)</td>
                        <td class="w-24 border border-slate-800 px-3 py-1.5 text-right">{{ number_format($quotation->subtotal, 2) }}-</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-800 bg-slate-100 px-3 py-1.5 text-right font-semibold">ส่วนลด</td>
                        <td class="border border-slate-800 px-3 py-1.5 text-right">{{ number_format($quotation->discount, 2) }}-</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-800 bg-slate-100 px-3 py-2 text-right text-sm font-bold">จำนวนเงินรวมทั้งสิ้น (บาท)</td>
                        <td class="border border-slate-800 px-3 py-2 text-right text-sm font-bold">{{ number_format($quotation->total, 2) }}-</td>
                    </tr>
                </table>
            </div>

            <!-- ลายเซ็น -->
            <div class="mt-auto pt-16">
                <div class="grid grid-cols-2 gap-10 text-xs">
                    <div class="text-center">
                        <div class="border-b border-dotted border-slate-500"></div>
                        <p class="mt-2 font-medium">ผู้เสนอราคา</p>
                        <p class="text-slate-500">วันที่ {{ $quotation->quote_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-center">
                        <div class="border-b border-dotted border-slate-500"></div>
                        <p class="mt-2 font-medium">ผู้อนุมัติ / ลูกค้า</p>
                        <p class="text-slate-500">วันที่ ......................</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ปุ่มดำเนินการ (ซ่อนเวลาพิมพ์) -->
        @if ($quotation->status === 'draft' || $quotation->status === 'sent')
            <div class="mt-5 flex justify-end gap-3 print:hidden">
                <form action="{{ route('quotations.update-status', $quotation->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="rejected">
                    <button type="submit"
                            class="rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                            onclick="return confirm('ลูกค้าปฏิเสธใบเสนอราคานี้?')">ปฏิเสธ</button>
                </form>
                <form action="{{ route('quotations.convert', $quotation->id) }}" method="POST"
                      class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                    <select name="payment_method"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                        <option value="cash">เงินสด</option>
                        <option value="transfer">โอนเงิน</option>
                        <option value="credit_card">บัตรเครดิต</option>
                        @if ($quotation->customer_id)
                            <option value="credit">ขายเชื่อ</option>
                        @endif
                    </select>
                    <button type="submit"
                            class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 transition-colors"
                            onclick="return confirm('แปลงเป็นใบเสร็จและตัดสต็อกทันที?')">แปลงเป็นใบเสร็จ</button>
                </form>
            </div>
        @endif
    </div>
@endsection
