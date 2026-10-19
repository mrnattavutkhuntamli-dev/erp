@extends('layouts.app')

@section('title', 'ภาพรวมระบบ')

{{-- ปุ่มลัดบน header --}}
@section('header_actions')
    <a href="{{ route('stock-documents.index') }}"
       class="hidden sm:inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
        ใบรับ/ใบเบิกสินค้า
    </a>
    <a href="{{ route('products.index') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
        จัดการสินค้า
    </a>
@endsection

@section('content')
    <div class="space-y-6">

        <!-- สรุปตัวเลขสำคัญ -->
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">

            <!-- สินค้าทั้งหมด -->
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 ring-1 ring-slate-200">
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

            <!-- มูลค่าสต็อก -->
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 ring-1 ring-slate-200">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-slate-500">มูลค่าสต็อกรวม (ราคาขาย)</p>
                    <p class="mt-0.5 text-2xl font-semibold text-slate-900 truncate">
                        ฿{{ number_format($totalStockValue, 2) }}
                    </p>
                </div>
            </div>

            <!-- ซัพพลายเออร์ -->
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 ring-1 ring-slate-200 sm:col-span-2 xl:col-span-1">
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
        </section>

        <!-- สินค้าสต็อกเหลือน้อย -->
        <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">สินค้าที่สต็อกเหลือน้อย</h3>
                    <p class="text-sm text-slate-500">สินค้าที่เหลือต่ำกว่า 10 ชิ้น ควรสั่งเพิ่มหรือเติมสต็อก</p>
                </div>
                @if ($lowStockProducts->count() > 0)
                    <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-700">
                        {{ number_format($lowStockProducts->count()) }} รายการ
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[32rem] text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">SKU</th>
                            <th class="px-5 py-3 font-medium">ชื่อสินค้า</th>
                            <th class="px-5 py-3 font-medium">สต็อกคงเหลือ</th>
                            <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($lowStockProducts as $product)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-3.5 text-slate-500">{{ $product->sku }}</td>
                                <td class="px-5 py-3.5 font-medium text-slate-900">{{ $product->name }}</td>
                                <td class="px-5 py-3.5">
                                    @if ($product->stock <= 0)
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">หมดสต็อก</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                            เหลือ {{ number_format($product->stock) }} ชิ้น
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="{{ route('products.edit', $product->id) }}"
                                       class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:border-ink-800 hover:bg-ink-800 hover:text-white transition-colors">
                                        เติมสต็อก
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center">
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
@endsection
