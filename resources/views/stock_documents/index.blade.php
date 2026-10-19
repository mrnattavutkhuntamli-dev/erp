@extends('layouts.app')

@section('title', 'ประวัติใบรับ/ใบเบิก')

@section('content')
    <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

        <!-- แถบหัวตาราง -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">รายการเอกสารคลังสินค้า</h1>
                <p class="text-sm text-slate-500">ทั้งหมด {{ number_format($documents->total()) }} เอกสาร</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('stock-documents.create', ['type' => 'in']) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3"/></svg>
                    ใบรับสินค้า (IN)
                </a>
                <a href="{{ route('stock-documents.create', ['type' => 'out']) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/></svg>
                    ใบเบิกสินค้า (OUT)
                </a>
            </div>
        </div>

        <!-- ตาราง -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">เลขที่เอกสาร</th>
                        <th class="px-5 py-3 font-medium">ประเภท</th>
                        <th class="px-5 py-3 font-medium">วันที่</th>
                        <th class="px-5 py-3 font-medium">ผู้รับ/ผู้ส่ง</th>
                        <th class="px-5 py-3 font-medium">พนักงาน</th>
                        <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($documents as $doc)
                        @php $voided = $doc->status === 'voided'; @endphp

                        {{-- เอกสารที่ยกเลิกแล้ว: พื้นเทาและตัวอักษรจาง --}}
                        <tr class="transition-colors {{ $voided ? 'bg-slate-50 text-slate-400' : 'hover:bg-slate-50 text-slate-600' }}">

                            <td class="px-5 py-3.5">
                                <a href="{{ route('stock-documents.show', $doc->id) }}"
                                   class="font-medium hover:underline {{ $voided ? 'text-slate-400 line-through' : 'text-sky-700' }}">
                                    {{ $doc->doc_no }}
                                </a>
                                @if ($voided)
                                    <span class="ml-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600">ยกเลิกแล้ว</span>
                                @endif
                            </td>

                            <td class="px-5 py-3.5">
                                @if ($doc->type == 'in')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 {{ $voided ? 'opacity-60' : '' }}">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3"/></svg>
                                        รับเข้า (IN)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700 {{ $voided ? 'opacity-60' : '' }}">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/></svg>
                                        เบิกออก (OUT)
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-3.5">{{ \Carbon\Carbon::parse($doc->date)->format('d/m/Y') }}</td>

                            <td class="px-5 py-3.5">
                                @if ($doc->type == 'in')
                                    {{ $doc->supplier->name ?? '-' }}
                                @else
                                    {{ $doc->customer->name ?? $doc->receiver_name ?? '-' }}
                                @endif
                            </td>

                            <td class="px-5 py-3.5">{{ $doc->user->name ?? '-' }}</td>

                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('stock-documents.show', $doc->id) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        ดู
                                    </a>

                                    @if (!$voided)
                                        <a href="{{ route('stock-documents.edit', $doc->id) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                            แก้ไข
                                        </a>

                                        <form action="{{ route('stock-documents.destroy', $doc->id) }}" method="POST"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกเอกสารนี้? สต็อกจะถูกคืนกลับอัตโนมัติ');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-600 hover:border-red-600 hover:text-white transition-colors">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                ยกเลิก
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                </div>
                                <p class="font-medium text-slate-900">ยังไม่มีประวัติเอกสาร</p>
                                <p class="text-sm text-slate-500">สร้างใบรับหรือใบเบิกสินค้าจากปุ่มด้านบน</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- เลขหน้า -->
        @if ($documents->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $documents->links() }}
            </div>
        @endif
    </section>
@endsection
