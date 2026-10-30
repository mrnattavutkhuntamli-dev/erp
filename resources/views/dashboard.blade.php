@extends('layouts.app')

@section('title', 'ภาพรวมระบบ')

@section('content')
    <div class="w-full space-y-6">

        <!-- สรุปตัวเลขสำคัญ -->
        <section aria-label="สรุปตัวเลขสำคัญ" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 sm:gap-5">

            @if ($totalStockValue !== null)
            <!-- มูลค่าสต็อก (การ์ดเด่น) -->
            <div class="relative overflow-hidden rounded-2xl bg-ink-900 p-6 text-white shadow-sm sm:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500 text-ink-950">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                    </div>
                    <p class="text-sm text-slate-300">มูลค่าสต็อกรวม (ราคาทุน)</p>
                </div>
                <p class="mt-5 break-words text-3xl font-semibold tracking-tight sm:text-4xl">
                    <span class="text-amber-400">฿</span>{{ number_format($totalStockValue, 2) }}
                </p>
            </div>

            @endif

            @if ($canSeeStock)
            <!-- สินค้าทั้งหมด -->
            <div class="flex items-center gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-slate-500">สินค้าทั้งหมด</p>
                    <p class="mt-0.5 text-2xl font-semibold text-slate-900">
                        {{ number_format($totalProducts) }}
                        <span class="text-sm font-normal text-slate-500">รายการ</span>
                    </p>
                </div>
            </div>

            @endif

            @if ($canSeeSuppliers)
            <!-- ซัพพลายเออร์ -->
            <div class="flex items-center gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-slate-500">ซัพพลายเออร์</p>
                    <p class="mt-0.5 text-2xl font-semibold text-slate-900">
                        {{ number_format($totalSuppliers) }}
                        <span class="text-sm font-normal text-slate-500">ราย</span>
                    </p>
                </div>
            </div>
            @endif
        </section>

        @if ($canSeeStock)
        <!-- สินค้าสต็อกเหลือน้อย -->
        <section aria-labelledby="low-stock-heading" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl {{ $lowStockProducts->count() > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    </div>
                    <div>
                        <h3 id="low-stock-heading" class="text-base font-semibold text-slate-900">สินค้าที่สต็อกเหลือน้อย</h3>
                        <p class="text-sm text-slate-500">เหลือต่ำกว่า 10 ชิ้น ควรสั่งเพิ่มหรือเติมสต็อก</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if ($lowStockProducts->count() > 0)
                        <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-700 ring-1 ring-red-100">
                            {{ number_format($lowStockProducts->count()) }} รายการ
                        </span>
                    @endif
                    @if (Route::has('products.index'))
                        <a href="{{ route('products.index') }}" class="text-sm font-medium text-ink-700 underline-offset-4 hover:underline">ดูสินค้าทั้งหมด</a>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">SKU</th>
                            <th scope="col" class="px-5 py-3 font-medium">ชื่อสินค้า</th>
                            <th scope="col" class="px-5 py-3 font-medium">สต็อกคงเหลือ</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($lowStockProducts as $product)
                            @php
                                $out = $product->stock <= 0;
                                $pct = $out ? 0 : min(100, max(6, ($product->stock / 10) * 100));
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-500 sm:px-6">{{ $product->sku }}</td>
                                <td class="px-5 py-3.5 font-medium text-slate-900">{{ $product->name }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-1.5 w-20 flex-shrink-0 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                                            <div class="h-full rounded-full {{ $out ? 'bg-red-500' : 'bg-amber-500' }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        @if ($out)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">หมดสต็อก</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                                เหลือ {{ number_format($product->stock) }} ชิ้น
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right sm:px-6">
                                    <a href="{{ route('products.edit', $product->id) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:border-ink-800 hover:bg-ink-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        เติมสต็อก
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-14 text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </div>
                                    <p class="font-medium text-slate-900">สต็อกอยู่ในระดับปกติ</p>
                                    <p class="text-sm text-slate-500">ตอนนี้ไม่มีสินค้าที่ต่ำกว่า 10 ชิ้น</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endif
@endsection
