@extends('layouts.app')

@section('title', 'จัดการสินค้า')

@section('content')
    @php
        $hasFilter = filled($search) || filled($categoryId);
    @endphp

    <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

        <!-- แถบหัวตาราง -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">รายการสินค้าทั้งหมด</h1>
                <p class="text-sm text-slate-500">
                    @if ($products->total() > 0)
                        แสดง {{ number_format($products->firstItem()) }}–{{ number_format($products->lastItem()) }}
                        จาก {{ number_format($products->total()) }} รายการ{{ $hasFilter ? ' (ตามเงื่อนไขที่เลือก)' : '' }}
                    @else
                        ไม่พบรายการ
                    @endif
                </p>
            </div>
            <a href="{{ route('products.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                เพิ่มสินค้า
            </a>
        </div>

        <!-- แถบค้นหา / กรอง -->
        <form method="GET" action="{{ route('products.index') }}"
              class="flex flex-wrap items-end gap-3 border-b border-slate-200 bg-slate-50/60 px-5 py-3">

            <div class="min-w-[16rem] flex-1">
                <label for="q" class="mb-1 block text-xs font-medium text-slate-500">ค้นหา</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
                    <input type="search" id="q" name="q" value="{{ $search }}"
                           placeholder="ชื่อสินค้า, SKU หรือยี่ห้อ"
                           class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                </div>
            </div>

            <div class="w-full sm:w-52">
                <label for="category_id" class="mb-1 block text-xs font-medium text-slate-500">หมวดหมู่</label>
                <select id="category_id" name="category_id" onchange="this.form.submit()"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) $categoryId === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-28">
                <label for="per_page" class="mb-1 block text-xs font-medium text-slate-500">แสดงต่อหน้า</label>
                <select id="per_page" name="per_page" onchange="this.form.submit()"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                    @foreach ($perPageOptions as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} รายการ</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="h-10 rounded-lg bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                    ค้นหา
                </button>
                @if ($hasFilter)
                    <a href="{{ route('products.index', ['per_page' => $perPage]) }}"
                       class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors">
                        ล้างตัวกรอง
                    </a>
                @endif
            </div>
        </form>

        <!-- ตาราง -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">สินค้า</th>
                        <th class="px-5 py-3 font-medium">SKU</th>
                        <th class="px-5 py-3 font-medium">หมวดหมู่</th>
                        <th class="px-5 py-3 font-medium text-right">ราคาต้นทุน</th>
                        <th class="px-5 py-3 font-medium text-right">ราคาขาย</th>
                        <th class="px-5 py-3 font-medium">สต็อก</th>
                        <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50 transition-colors">

                            {{-- รูป + ชื่อสินค้า + ยี่ห้อ --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($product->primary_image_path)
                                        <img src="{{ asset('storage/' . $product->primary_image_path) }}" alt="รูป {{ $product->name }}"
                                             loading="lazy"
                                             class="h-12 w-12 flex-shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                                    @else
                                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400" title="ไม่มีรูป">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0021.75 19.5V4.5a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v15A1.5 1.5 0 003.75 21zM15 8.25h.008v.008H15V8.25z"/></svg>
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-900">{{ $product->name }}</div>
                                        @if (!empty($product->brand_name))
                                            <div class="mt-0.5 inline-flex items-center gap-1 text-xs text-sky-700">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z"/></svg>
                                                {{ $product->brand_name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3 text-slate-500">{{ $product->sku }}</td>

                            {{-- หมวดหมู่ (กดเพื่อกรองเฉพาะหมวดนี้) --}}
                            <td class="px-5 py-3">
                                @if (!empty($product->category_name))
                                    <a href="{{ route('products.index', ['category_id' => $product->category_id, 'per_page' => $perPage]) }}"
                                       title="ดูเฉพาะหมวดนี้"
                                       class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 hover:bg-slate-200 transition-colors">{{ $product->category_name }}</a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-right text-slate-500">฿{{ number_format($product->cost, 2) }}</td>
                            <td class="px-5 py-3 text-right font-medium text-slate-900">฿{{ number_format($product->price, 2) }}</td>

                            {{-- สต็อก: ต่ำกว่า 10 เป็นสีเตือน --}}
                            <td class="px-5 py-3">
                                @if ($product->stock <= 0)
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">หมดสต็อก</span>
                                @elseif ($product->stock < 10)
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                        {{ number_format($product->stock) }} {{ $product->unit_name ?? '' }}
                                    </span>
                                @else
                                    <span class="text-slate-800">{{ number_format($product->stock) }}</span>
                                    <span class="text-slate-500">{{ $product->unit_name ?? '' }}</span>
                                @endif
                            </td>

                            {{-- ปุ่มจัดการ --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('products.edit', $product->id) }}"
                                       title="แก้ไข" aria-label="แก้ไข {{ $product->name }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        แก้ไข
                                    </a>

                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                          onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบสินค้านี้?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="ลบ" aria-label="ลบ {{ $product->name }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-600 hover:border-red-600 hover:text-white transition-colors">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            ลบ
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                                </div>

                                @if ($hasFilter)
                                    <p class="font-medium text-slate-900">ไม่พบสินค้าที่ตรงกับเงื่อนไข</p>
                                    <p class="mb-4 text-sm text-slate-500">ลองเปลี่ยนคำค้นหา หรือเลือกหมวดหมู่อื่น</p>
                                    <a href="{{ route('products.index', ['per_page' => $perPage]) }}"
                                       class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        ล้างตัวกรอง
                                    </a>
                                @else
                                    <p class="font-medium text-slate-900">ยังไม่มีสินค้าในระบบ</p>
                                    <p class="mb-4 text-sm text-slate-500">เริ่มจากเพิ่มสินค้าชิ้นแรกของคุณ</p>
                                    <a href="{{ route('products.create') }}"
                                       class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                                        เพิ่มสินค้า
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- เลขหน้า -->
        @if ($products->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $products->links() }}
            </div>
        @endif
    </section>
@endsection
