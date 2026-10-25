@extends('layouts.app')

@section('title', 'ประวัติการขาย')

@section('content')
    <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">

        <!-- แถบหัวตาราง -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">ประวัติการขาย</h1>
                <p class="text-sm text-slate-500">
                    ยอดขายรวมวันนี้
                    <span class="ml-1 font-semibold text-emerald-600">฿{{ number_format($todaySales, 2) }}</span>
                </p>
            </div>
            <a href="{{ route('sales.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                เปิดหน้าขาย (POS)
            </a>
        </div>

        <!-- ข้อความแจ้งเตือน -->
        @if (session('success'))
            <div class="mx-5 mt-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mx-5 mt-4 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- ตาราง -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">เลขที่ใบเสร็จ</th>
                        <th class="px-5 py-3 font-medium">วันที่/เวลา</th>
                        <th class="px-5 py-3 font-medium">ลูกค้า</th>
                        <th class="px-5 py-3 font-medium text-right">ยอดสุทธิ</th>
                        <th class="px-5 py-3 font-medium">วิธีชำระ</th>
                        <th class="px-5 py-3 font-medium">พนักงาน</th>
                        <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        @php $voided = $sale->status === 'voided'; @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ $voided ? 'bg-red-50/50' : '' }}">

                            {{-- เลขที่ใบเสร็จ + สถานะ --}}
                            <td class="px-5 py-3">
                                <div class="font-medium {{ $voided ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $sale->invoice_no }}</div>
                                @if ($voided)
                                    <span class="mt-0.5 inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">ยกเลิกแล้ว</span>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-slate-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>

                            {{-- ลูกค้า + ทะเบียนรถ --}}
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900">{{ $sale->customer->name ?? 'ลูกค้าทั่วไป' }}</div>
                                @if ($sale->customer && $sale->customer->license_plate)
                                    <div class="mt-0.5 inline-flex items-center gap-1 text-xs text-sky-700">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                                        {{ $sale->customer->license_plate }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-right font-medium {{ $voided ? 'text-slate-400 line-through' : 'text-slate-900' }}">฿{{ number_format($sale->total, 2) }}</td>

                            {{-- วิธีชำระ + สถานะการชำระ --}}
                            <td class="px-5 py-3">
                                @if ($sale->payment_status === 'unpaid')
                                    <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">📋 ขายเชื่อ (ค้างชำระ)</span>
                                @elseif ($sale->payment_status === 'partial')
                                    <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">ชำระบางส่วน</span>
                                @else
                                    @if ($sale->payment_method == 'cash')
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">เงินสด</span>
                                    @elseif ($sale->payment_method == 'transfer')
                                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium text-sky-700">โอนเงิน</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-700">บัตรเครดิต</span>
                                    @endif
                                @endif
                            </td>

                            <td class="px-5 py-3 text-slate-500">{{ $sale->user->name }}</td>

                            {{-- ปุ่มจัดการ --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('sales.show', $sale->id) }}"
                                       title="ดูใบเสร็จ" aria-label="ดูใบเสร็จ {{ $sale->invoice_no }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        ดูใบเสร็จ
                                    </a>

                                    @if (! $voided)
                                        <button type="button" onclick="openVoidModal({{ $sale->id }}, '{{ $sale->invoice_no }}')"
                                                title="ยกเลิก" aria-label="ยกเลิก {{ $sale->invoice_no }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-600 hover:border-red-600 hover:text-white transition-colors">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            ยกเลิก
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                </div>
                                <p class="font-medium text-slate-900">ยังไม่มีประวัติการขาย</p>
                                <p class="mb-4 text-sm text-slate-500">เริ่มขายจากหน้า POS แล้วรายการจะแสดงที่นี่</p>
                                <a href="{{ route('sales.index') }}"
                                   class="inline-flex items-center gap-2 rounded-lg bg-ink-900 px-4 py-2 text-sm font-medium text-white hover:bg-ink-800 transition-colors">
                                    เปิดหน้าขาย (POS)
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- เลขหน้า -->
        @if ($sales->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $sales->withQueryString()->links() }}
            </div>
        @endif
    </section>

    <!-- ✅ Modal สำหรับ Void ใบเสร็จ -->
    <div id="voidModal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-slate-800 mb-4">⚠️ ยกเลิกใบเสร็จ</h3>
            <p class="text-sm text-slate-600 mb-2">เลขที่: <span id="void-invoice-no" class="font-bold text-slate-900"></span></p>
            <p class="text-sm text-slate-500 mb-4">การยกเลิกจะคืนสต็อกสินค้าอัตโนมัติ และไม่สามารถย้อนกลับได้</p>
            <form id="void-form" method="POST">
                @csrf
                @method('DELETE')
                <label class="block text-sm font-medium text-slate-700 mb-2">เหตุผลการยกเลิก <span class="text-red-500">*</span></label>
                <textarea name="void_reason" required rows="3" placeholder="เช่น ลูกค้าคืนสินค้า, บันทึกผิด, ฯลฯ" class="w-full border border-slate-300 p-2 rounded-lg mb-4 focus:ring-red-500 focus:border-red-500"></textarea>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeVoidModal()" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-300 transition">ยกเลิก</button>
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">ยืนยันการยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openVoidModal(saleId, invoiceNo) {
            document.getElementById('void-form').action = '/sales/' + saleId;
            document.getElementById('void-invoice-no').textContent = invoiceNo;
            document.getElementById('voidModal').classList.remove('hidden');
            document.getElementById('voidModal').classList.add('flex');
        }
        function closeVoidModal() {
            document.getElementById('voidModal').classList.add('hidden');
            document.getElementById('voidModal').classList.remove('flex');
        }
    </script>
@endsection
