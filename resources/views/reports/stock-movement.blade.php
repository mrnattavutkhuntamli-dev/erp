@extends('layouts.app')
@section('title', 'ความเคลื่อนไหวสต็อก')

@php
    $startDate = request('start_date', $startDate ?? null);
    $endDate   = request('end_date', $endDate ?? null);

    $presets = [
        'วันนี้'      => [now()->toDateString(), now()->toDateString()],
        '7 วันล่าสุด'  => [now()->subDays(6)->toDateString(), now()->toDateString()],
        'เดือนนี้'     => [now()->startOfMonth()->toDateString(), now()->toDateString()],
    ];

    $hasFilter = request()->hasAny(['start_date', 'end_date', 'product_id', 'type']);
    $selectedProduct = request('product_id') ? $products->firstWhere('id', (int) request('product_id')) : null;

    // สีของแหล่งที่มา
    $sourceStyle = fn ($s) => match (true) {
        str_contains($s, 'POS')      => 'bg-sky-50 text-sky-700 ring-sky-200',
        str_contains($s, 'ปรับปรุง') => 'bg-amber-50 text-amber-700 ring-amber-200',
        default                      => 'bg-violet-50 text-violet-700 ring-violet-200',
    };

    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm
              focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30';
@endphp

@section('content')
<div class="w-full space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">ความเคลื่อนไหวสต็อก</h1>
            <p class="mt-1 text-sm text-slate-500">ประวัติการเข้า-ออกของสินค้าทั้งหมด จากการรับเข้า การขาย และการปรับปรุงสต็อก</p>
        </div>
        <div class="text-sm text-slate-500">
            พบ <span class="text-lg font-semibold text-slate-900">{{ number_format($paginator->total()) }}</span> รายการ
        </div>
    </div>

    {{-- Filter --}}
    <form action="{{ route('reports.stock-movement') }}" method="GET"
          class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-2">
                <label for="start_date" class="mb-1.5 block text-xs font-medium text-slate-600">ตั้งแต่วันที่</label>
                <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="{{ $input }}">
            </div>
            <div class="lg:col-span-2">
                <label for="end_date" class="mb-1.5 block text-xs font-medium text-slate-600">ถึงวันที่</label>
                <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="{{ $input }}">
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <label for="product_id" class="mb-1.5 block text-xs font-medium text-slate-600">สินค้า</label>
                <select id="product_id" name="product_id" class="{{ $input }}">
                    <option value="">ทั้งหมด</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>
                            {{ $product->sku }} - {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-slate-600">ประเภท</label>
                <div class="grid grid-cols-3 overflow-hidden rounded-lg border border-slate-300 bg-slate-50 text-sm font-medium">
                    @foreach(['' => 'ทั้งหมด', 'in' => 'เข้า', 'out' => 'ออก'] as $val => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="{{ $val }}" class="peer sr-only"
                                   @checked((string) request('type') === (string) $val)>
                            <span class="block px-2 py-2 text-center text-slate-600 transition peer-checked:bg-blue-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-inset peer-focus-visible:ring-blue-500">
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
                <button type="submit"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.48l4.31 4.3a1 1 0 01-1.4 1.42l-4.32-4.31A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                    กรองข้อมูล
                </button>
                @if($hasFilter)
                    <a href="{{ route('reports.stock-movement') }}"
                       class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 transition hover:bg-slate-100">ล้าง</a>
                @endif
            </div>
        </div>

        {{-- ช่วงเวลาด่วน --}}
        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 text-sm">
            <span class="text-slate-500">ช่วงเวลาด่วน</span>
            @foreach($presets as $label => [$from, $to])
                @php $active = $startDate === $from && $endDate === $to; @endphp
                <a href="{{ route('reports.stock-movement', array_merge(request()->except(['page', 'start_date', 'end_date']), ['start_date' => $from, 'end_date' => $to])) }}"
                   class="rounded-full px-3 py-1 text-xs font-medium ring-1 transition
                          {{ $active ? 'bg-blue-50 text-blue-700 ring-blue-300' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach

            @if($selectedProduct)
                <span class="ml-auto inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                    กำลังดู: {{ $selectedProduct->name }}
                </span>
            @endif
        </div>
    </form>

    {{-- ข้อมูล --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

        {{-- Desktop table --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full border-collapse text-left text-sm">
                <thead class="sticky top-0 bg-slate-50 text-xs text-slate-500">
                    <tr class="border-b border-slate-200">
                        <th class="px-4 py-3 font-medium">วันที่</th>
                        <th class="px-4 py-3 font-medium">เอกสารอ้างอิง</th>
                        <th class="px-4 py-3 font-medium">สินค้า</th>
                        <th class="px-4 py-3 text-right font-medium">จำนวน</th>
                        <th class="px-4 py-3 text-right font-medium">คงเหลือ</th>
                        <th class="px-4 py-3 font-medium">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($paginator as $m)
                        @php
                            $isIn = $m->type === 'in';
                            $dt   = \Carbon\Carbon::parse($m->date);
                        @endphp
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap border-l-4 px-4 py-3 {{ $isIn ? 'border-emerald-400' : 'border-rose-400' }}">
                                <div class="font-medium text-slate-800">{{ $dt->format('d/m/Y') }}</div>
                                <div class="text-xs text-slate-400">{{ $dt->format('H:i') }} น.</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $m->reference }}</div>
                                <span class="mt-1 inline-block rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $sourceStyle($m->source) }}">
                                    {{ $m->source }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $m->product_name ?? 'สินค้าถูกลบ' }}</div>
                                <div class="text-xs text-slate-400">{{ $m->sku ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm font-semibold tabular-nums
                                             {{ $isIn ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        @if($isIn)
                                            <path d="M10 3l6 7h-4v7H8v-7H4l6-7z"/>
                                        @else
                                            <path d="M10 17l-6-7h4V3h4v7h4l-6 7z"/>
                                        @endif
                                    </svg>
                                    <span class="sr-only">{{ $isIn ? 'รับเข้า' : 'จ่ายออก' }}</span>
                                    {{ $isIn ? '+' : '-' }}{{ number_format(abs($m->quantity)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-700">
                                {{ $m->stock_after !== null ? number_format($m->stock_after) : '-' }}
                            </td>
                            <td class="max-w-[16rem] truncate px-4 py-3 text-xs text-slate-500" title="{{ $m->note }}">
                                {{ $m->note ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                                    <svg class="h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <p class="font-medium text-slate-700">ไม่พบความเคลื่อนไหวสต็อก</p>
                                    <p class="text-sm text-slate-500">ลองขยายช่วงวันที่ หรือเปลี่ยนสินค้าและประเภทที่เลือก</p>
                                    @if($hasFilter)
                                        <a href="{{ route('reports.stock-movement') }}" class="mt-2 text-sm font-medium text-blue-600 hover:underline">ล้างตัวกรองทั้งหมด</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <ul class="divide-y divide-slate-100 md:hidden">
            @forelse($paginator as $m)
                @php
                    $isIn = $m->type === 'in';
                    $dt   = \Carbon\Carbon::parse($m->date);
                @endphp
                <li class="flex gap-3 border-l-4 px-4 py-3 {{ $isIn ? 'border-emerald-400' : 'border-rose-400' }}">
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-slate-800">{{ $m->product_name ?? 'สินค้าถูกลบ' }}</div>
                        <div class="text-xs text-slate-400">{{ $m->sku ?? '-' }}</div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                            <span class="rounded-md px-1.5 py-0.5 font-medium ring-1 ring-inset {{ $sourceStyle($m->source) }}">{{ $m->source }}</span>
                            <span>{{ $m->reference }}</span>
                        </div>
                        <div class="mt-1 text-xs text-slate-400">{{ $dt->format('d/m/Y H:i') }} น.</div>
                        @if($m->note)
                            <div class="mt-1 text-xs text-slate-500">{{ $m->note }}</div>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-base font-semibold tabular-nums {{ $isIn ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $isIn ? '+' : '-' }}{{ number_format(abs($m->quantity)) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-400">
                            คงเหลือ {{ $m->stock_after !== null ? number_format($m->stock_after) : '-' }}
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center">
                    <p class="font-medium text-slate-700">ไม่พบความเคลื่อนไหวสต็อก</p>
                    <p class="mt-1 text-sm text-slate-500">ลองขยายช่วงวันที่ หรือเปลี่ยนตัวกรอง</p>
                </li>
            @endforelse
        </ul>

        @if($paginator->hasPages())
            <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/60 px-4 py-3 sm:flex-row">
                <p class="text-xs text-slate-500">
                    แสดง {{ number_format($paginator->firstItem()) }}-{{ number_format($paginator->lastItem()) }}
                    จาก {{ number_format($paginator->total()) }} รายการ
                </p>
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
