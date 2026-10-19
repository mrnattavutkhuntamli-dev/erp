@extends('layouts.app')

@section('title', 'จัดการซัพพลายเออร์')

@section('content')
    {{-- Alerts --}}
    @if (session('success'))
        <div class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
            <svg class="h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
            <svg class="h-5 w-5 flex-shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

        <!-- แถบหัวตาราง -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">รายการซัพพลายเออร์ทั้งหมด</h1>
                <p class="text-sm text-slate-500">
                    ทั้งหมด {{ number_format(method_exists($suppliers, 'total') ? $suppliers->total() : $suppliers->count()) }} รายการ
                </p>
            </div>
            <a href="{{ route('suppliers.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                เพิ่มซัพพลายเออร์
            </a>
        </div>

        <!-- ตาราง -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">ชื่อบริษัท/ร้าน</th>
                        <th class="px-5 py-3 font-medium">ผู้ติดต่อ</th>
                        <th class="px-5 py-3 font-medium">เบอร์โทร</th>
                        <th class="px-5 py-3 font-medium">อีเมล</th>
                        <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($suppliers as $supplier)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-600 ring-1 ring-slate-200">
                                        {{ mb_strtoupper(mb_substr($supplier->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $supplier->name }}</span>
                                </div>
                            </td>

                            <td class="px-5 py-3 text-slate-600">{{ $supplier->contact_name ?? '-' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $supplier->phone ?? '-' }}</td>

                            <td class="px-5 py-3">
                                @if (!empty($supplier->email))
                                    <a href="mailto:{{ $supplier->email }}" class="text-sky-700 hover:underline">{{ $supplier->email }}</a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            {{-- ปุ่มจัดการ --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('suppliers.edit', $supplier->id) }}"
                                       title="แก้ไข" aria-label="แก้ไข {{ $supplier->name }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        แก้ไข
                                    </a>

                                    <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST"
                                          onsubmit="return confirm('ยืนยันการลบ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="ลบ" aria-label="ลบ {{ $supplier->name }}"
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
                            <td colspan="5" class="px-5 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                                </div>
                                <p class="font-medium text-slate-900">ยังไม่มีซัพพลายเออร์ในระบบ</p>
                                <p class="mb-4 text-sm text-slate-500">เริ่มจากเพิ่มซัพพลายเออร์รายแรกของคุณ</p>
                                <a href="{{ route('suppliers.create') }}"
                                   class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                                    เพิ่มซัพพลายเออร์
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- เลขหน้า -->
        @if (method_exists($suppliers, 'hasPages') && $suppliers->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $suppliers->withQueryString()->links() }}
            </div>
        @endif
    </section>
@endsection
