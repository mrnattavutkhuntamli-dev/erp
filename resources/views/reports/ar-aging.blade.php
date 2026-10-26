@extends('layouts.app')
@section('title', 'รายงานลูกหนี้ (A/R)')

@php
    $grand = max((float) $totals['total'], 0.01);
    $pCurrent = round($totals['current'] / $grand * 100, 1);
    $p3160    = round($totals['days_31_60'] / $grand * 100, 1);
    $p60      = round($totals['days_60_plus'] / $grand * 100, 1);
    $customerCount = count($agingData);
    $invoiceCount  = collect($agingData)->sum(fn ($c) => count($c['invoices']));

    // ลำดับความเสี่ยง: ลูกค้าที่มีบิลเก่าสุดขึ้นก่อน
    $sorted = collect($agingData)->sortByDesc(fn ($c) => collect($c['invoices'])->max('days'));
@endphp

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap');
    .ar-page { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; }
    .ar-num  { font-variant-numeric: tabular-nums; }
    .ar-page details > summary { list-style: none; }
    .ar-page details > summary::-webkit-details-marker { display: none; }
    .ar-page details[open] .ar-chevron { transform: rotate(180deg); }
    .ar-chevron { transition: transform .2s ease; }
    .ar-bar > div { transition: width .9s cubic-bezier(.2,.8,.2,1); }
    @media print { .no-print { display: none !important; } }
    @media (prefers-reduced-motion: reduce) { .ar-bar > div, .ar-chevron { transition: none; } }
</style>
@endpush

@section('content')
<div class="ar-page w-full space-y-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">รายงานลูกหนี้ค้างชำระ</h1>
            <p class="text-sm text-slate-500 mt-1">
                A/R Aging · ข้อมูล ณ วันที่ {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
        <a href="{{ route('reports.ar-aging.export-pdf') }}"
           class="no-print inline-flex items-center gap-2 self-start sm:self-auto bg-slate-900 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-900 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
            ดาวน์โหลด PDF
        </a>
    </div>

    {{-- Hero: ยอดรวม + แถบสัดส่วนอายุหนี้ --}}
    <section class="bg-slate-900 text-white rounded-2xl p-6 sm:p-8 shadow-lg">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <p class="text-sm text-slate-400">ยอดค้างชำระรวม</p>
                <p class="ar-num text-5xl sm:text-6xl font-bold mt-2 leading-none">
                    <span class="text-slate-500 font-medium text-3xl mr-1">฿</span>{{ number_format($totals['total'], 2) }}
                </p>
            </div>
            <div class="flex gap-8 text-sm">
                <div>
                    <p class="text-slate-400">ลูกค้าที่ค้างชำระ</p>
                    <p class="ar-num text-2xl font-semibold mt-1">{{ number_format($customerCount) }} <span class="text-sm text-slate-400 font-normal">ราย</span></p>
                </div>
                <div>
                    <p class="text-slate-400">บิลที่ค้าง</p>
                    <p class="ar-num text-2xl font-semibold mt-1">{{ number_format($invoiceCount) }} <span class="text-sm text-slate-400 font-normal">ใบ</span></p>
                </div>
            </div>
        </div>

        {{-- แถบสัดส่วน --}}
        <div class="ar-bar mt-8 flex h-3 w-full overflow-hidden rounded-full bg-slate-700" role="img"
             aria-label="สัดส่วนอายุหนี้: 0-30 วัน {{ $pCurrent }}%, 31-60 วัน {{ $p3160 }}%, เกิน 60 วัน {{ $p60 }}%">
            <div class="bg-teal-400"  style="width: {{ $pCurrent }}%"></div>
            <div class="bg-amber-400" style="width: {{ $p3160 }}%"></div>
            <div class="bg-rose-500"  style="width: {{ $p60 }}%"></div>
        </div>

        {{-- คำอธิบายแต่ละช่วง --}}
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="flex gap-3">
                <span class="mt-1.5 h-2.5 w-2.5 rounded-full bg-teal-400 shrink-0"></span>
                <div>
                    <p class="text-sm text-slate-300">ยังไม่ครบกำหนด (0-30 วัน)</p>
                    <p class="ar-num text-xl font-semibold">฿{{ number_format($totals['current'], 2) }}</p>
                    <p class="text-xs text-slate-500 ar-num">{{ $pCurrent }}% ของยอดค้าง</p>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="mt-1.5 h-2.5 w-2.5 rounded-full bg-amber-400 shrink-0"></span>
                <div>
                    <p class="text-sm text-slate-300">ค้าง 31-60 วัน</p>
                    <p class="ar-num text-xl font-semibold">฿{{ number_format($totals['days_31_60'], 2) }}</p>
                    <p class="text-xs text-slate-500 ar-num">{{ $p3160 }}% ของยอดค้าง</p>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="mt-1.5 h-2.5 w-2.5 rounded-full bg-rose-500 shrink-0"></span>
                <div>
                    <p class="text-sm text-slate-300">ค้างเกิน 60 วัน</p>
                    <p class="ar-num text-xl font-semibold text-rose-300">฿{{ number_format($totals['days_60_plus'], 2) }}</p>
                    <p class="text-xs text-slate-500 ar-num">{{ $p60 }}% ของยอดค้าง</p>
                </div>
            </div>
        </div>
    </section>

    @if($customerCount > 0)
    {{-- ค้นหา --}}
    <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-slate-800">รายชื่อลูกหนี้ <span class="text-sm font-normal text-slate-500">เรียงตามบิลที่ค้างนานที่สุด</span></h2>
        <div class="relative w-full sm:w-72">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
            <input id="arSearch" type="search" placeholder="ค้นหาชื่อลูกค้า หรือเบอร์โทร"
                   class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
        </div>
    </div>

    {{-- รายลูกค้า (คลิกเพื่อขยายดูบิล) --}}
    <div id="arList" class="space-y-3">
        @foreach($sorted as $customerId => $customer)
            @php
                $inv = collect($customer['invoices']);
                $oldest = (int) $inv->max('days');
                $cTotal = max((float) $customer['total'], 0.01);
                $c1 = round($inv->where('days', '<=', 30)->sum('due') / $cTotal * 100, 1);
                $c2 = round($inv->filter(fn ($i) => $i['days'] > 30 && $i['days'] <= 60)->sum('due') / $cTotal * 100, 1);
                $c3 = round($inv->where('days', '>', 60)->sum('due') / $cTotal * 100, 1);
                $accent = $oldest > 60 ? 'bg-rose-500' : ($oldest > 30 ? 'bg-amber-400' : 'bg-teal-400');
            @endphp
            <details class="ar-item group bg-white rounded-xl ring-1 ring-slate-200 shadow-sm overflow-hidden"
                     data-search="{{ Str::lower($customer['name'].' '.$customer['phone']) }}"
                     @if($loop->first && $oldest > 60) open @endif>
                <summary class="flex items-stretch cursor-pointer select-none hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-slate-900">
                    <span class="w-1.5 {{ $accent }} shrink-0"></span>
                    <div class="flex-1 min-w-0 px-4 sm:px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-slate-900 truncate">{{ $customer['name'] }}</h3>
                            <p class="text-sm text-slate-500 mt-0.5">โทร {{ $customer['phone'] }} · {{ $inv->count() }} บิล · ค้างนานสุด {{ $oldest }} วัน</p>
                        </div>
                        {{-- แถบอายุหนี้ของลูกค้ารายนี้ --}}
                        <div class="ar-bar hidden md:flex h-2 w-40 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                            <div class="bg-teal-400"  style="width: {{ $c1 }}%"></div>
                            <div class="bg-amber-400" style="width: {{ $c2 }}%"></div>
                            <div class="bg-rose-500"  style="width: {{ $c3 }}%"></div>
                        </div>
                        <div class="sm:text-right">
                            <p class="text-xs text-slate-500">ยอดค้างรวม</p>
                            <p class="ar-num text-xl font-bold text-slate-900">฿{{ number_format($customer['total'], 2) }}</p>
                        </div>
                    </div>
                    <span class="flex items-center pr-4 text-slate-400">
                        <svg class="ar-chevron w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </summary>

                <div class="border-t border-slate-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-5 py-2.5 text-left font-medium">เลขที่บิล</th>
                                <th class="px-5 py-2.5 text-left font-medium">วันที่ขาย</th>
                                <th class="px-5 py-2.5 text-right font-medium">ยอดรวม</th>
                                <th class="px-5 py-2.5 text-right font-medium">ชำระแล้ว</th>
                                <th class="px-5 py-2.5 text-right font-medium">ค้างชำระ</th>
                                <th class="px-5 py-2.5 text-center font-medium">อายุหนี้</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($customer['invoices'] as $row)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-3 font-medium text-slate-900">{{ $row['invoice_no'] }}</td>
                                    <td class="px-5 py-3 text-slate-600 ar-num">{{ $row['date'] }}</td>
                                    <td class="px-5 py-3 text-right text-slate-600 ar-num">฿{{ number_format($row['total'], 2) }}</td>
                                    <td class="px-5 py-3 text-right text-teal-700 ar-num">฿{{ number_format($row['paid'], 2) }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-900 ar-num">฿{{ number_format($row['due'], 2) }}</td>
                                    <td class="px-5 py-3 text-center">
                                        @if($row['days'] <= 30)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 text-teal-800 ring-1 ring-teal-200 px-2.5 py-0.5 text-xs font-medium"><span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>{{ $row['days'] }} วัน</span>
                                        @elseif($row['days'] <= 60)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-800 ring-1 ring-amber-200 px-2.5 py-0.5 text-xs font-medium"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $row['days'] }} วัน</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 text-rose-800 ring-1 ring-rose-200 px-2.5 py-0.5 text-xs font-medium"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>{{ $row['days'] }} วัน</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endforeach
    </div>

    <p id="arNoResult" class="hidden text-center text-slate-500 py-10">ไม่พบลูกค้าที่ตรงกับคำค้นหา ลองพิมพ์ชื่อหรือเบอร์โทรอีกครั้ง</p>

    @else
        {{-- ไม่มีลูกหนี้ --}}
        <div class="bg-white p-14 rounded-2xl ring-1 ring-slate-200 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-teal-50">
                <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-1">ไม่มียอดค้างชำระ</h3>
            <p class="text-slate-500">ลูกค้าทุกรายชำระครบแล้ว</p>
        </div>
    @endif
</div>

<script>
    (function () {
        var input = document.getElementById('arSearch');
        if (!input) return;
        var items = document.querySelectorAll('.ar-item');
        var empty = document.getElementById('arNoResult');
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase(), shown = 0;
            items.forEach(function (el) {
                var hit = !q || el.dataset.search.indexOf(q) !== -1;
                el.classList.toggle('hidden', !hit);
                if (hit) shown++;
            });
            empty.classList.toggle('hidden', shown > 0);
        });
    })();
</script>
@endsection
