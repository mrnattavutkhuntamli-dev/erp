@extends('layouts.app')
@section('title', 'เปิด-ปิดกะ')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">🕐 เปิด-ปิดกะ (Cashier Shift)</h1>
                <p class="text-sm text-slate-500 mt-1">จัดการเงินในลิ้นชักและสรุปยอดขายประจำกะ</p>
            </div>
            {{-- ปุ่มเปิดกะ (ถ้ายังกะไม่เปิด) --}}
            @if(!$currentShift)
                <button onclick="openOpenDrawer()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 shadow flex items-center gap-2 {{ !$todaySchedule ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$todaySchedule ? 'disabled' : '' }}>
                    🔓 เปิดกะ
                </button>
            @endif
        </div>

        {{-- แจ้งเตือน --}}
        @if (session('success'))
            <div class="bg-emerald-100 text-emerald-700 p-4 rounded-lg border border-emerald-200">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 text-red-700 p-4 rounded-lg border border-red-200">{{ session('error') }}</div>
        @endif

        {{-- กะปัจจุบัน --}}
        @if($currentShift)
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 p-6 rounded-xl shadow-lg text-white">
                <div class="flex justify-between items-start flex-wrap gap-4">
                    <div>
                        <p class="text-emerald-100 text-sm">กะปัจจุบัน (เปิดอยู่)</p>
                        <h2 class="text-2xl font-bold mt-1">เปิดกะเมื่อ {{ $currentShift->opened_at->format('d/m/Y H:i') }}</h2>
                        <div class="flex flex-wrap gap-4 mt-3 text-sm text-emerald-100">
                            <span>เงินทอนเริ่มต้น: <span class="font-bold text-white">฿{{ number_format($currentShift->opening_cash, 2) }}</span></span>
                        </div>
                    </div>
                    <button onclick="openCloseDrawer({{ $currentShift->id }}, {{ $currentShift->opening_cash }})" class="bg-white text-emerald-600 px-6 py-3 rounded-lg font-bold hover:bg-emerald-50 transition shadow-md">
                        🔒 ปิดกะ
                    </button>
                </div>
            </div>
        @else
            {{-- แจ้งเตือนถ้าไม่มีตารางกะ --}}
            @if(!$todaySchedule)
                <div class="bg-amber-50 border border-amber-200 p-4 rounded-lg">
                    <p class="text-sm text-amber-800">⚠️ วันนี้คุณไม่มีตารางกะ ไม่สามารถเปิดกะได้</p>
                </div>
            @else
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">
                        📅 ตารางกะวันนี้:
                        <span class="font-bold">{{ \Carbon\Carbon::parse($todaySchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($todaySchedule->end_time)->format('H:i') }}</span>
                    </p>
                </div>
            @endif
        @endif

        {{-- ประวัติกะ --}}
        <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
            <div class="p-4 border-b bg-slate-50">
                <h2 class="font-bold text-slate-800">📜 ประวัติกะ</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="p-3 font-medium">พนักงาน</th>
                            <th class="p-3 font-medium">เวลาเปิด</th>
                            <th class="p-3 font-medium">เวลาปิด</th>
                            <th class="p-3 font-medium text-right">เงินเริ่มต้น</th>
                            <th class="p-3 font-medium text-right">ยอดขายรวม</th>
                            <th class="p-3 font-medium text-right">เงินนับได้</th>
                            <th class="p-3 font-medium text-center">สถานะ</th>
                            <th class="p-3 font-medium text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($shifts as $shift)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-medium text-slate-800">{{ $shift->user->name }}</td>
                                <td class="p-3 text-slate-600">{{ $shift->opened_at->format('d/m/Y H:i') }}</td>
                                <td class="p-3 text-slate-600">{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="p-3 text-right">฿{{ number_format($shift->opening_cash, 2) }}</td>
                                <td class="p-3 text-right font-bold text-emerald-600">฿{{ number_format($shift->total_sales, 2) }}</td>
                                <td class="p-3 text-right">{{ $shift->closing_cash !== null ? '฿'.number_format($shift->closing_cash, 2) : '-' }}</td>
                                <td class="p-3 text-center">
                                    @if($shift->status === 'open')
                                        <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-xs font-bold">เปิดอยู่</span>
                                    @else
                                        <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-xs font-bold">ปิดแล้ว</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($shift->status === 'closed')
                                        <a href="{{ route('cashier-shifts.show', $shift->id) }}" class="text-blue-600 hover:underline text-xs">ดูสรุป</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-8 text-center text-slate-400 italic">ยังไม่มีประวัติกะ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t">{{ $shifts->links() }}</div>
        </div>
    </div>

    {{-- ============== Drawer: Open Shift ============== --}}
    <div id="openShiftBackdrop" onclick="closeOpenDrawer()" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm hidden transition-opacity"></div>
    <div id="openShiftPanel" class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
        <div class="px-6 py-5 border-b bg-emerald-50 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-slate-800">🔓 เปิดกะใหม่</h3>
                <p class="text-sm text-slate-500 mt-0.5">บันทึกเงินทอนเริ่มต้นในลิ้นชัก</p>
            </div>
            <button onclick="closeOpenDrawer()" class="text-slate-400 hover:text-slate-700 hover:bg-slate-200 w-9 h-9 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('cashier-shifts.store') }}" method="POST" class="flex-1 flex flex-col overflow-hidden">
            @csrf
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
                @if($todaySchedule)
                    <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg">
                        <p class="text-sm text-blue-800">
                            📅 กะวันนี้: <span class="font-bold">{{ \Carbon\Carbon::parse($todaySchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($todaySchedule->end_time)->format('H:i') }}</span>
                        </p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">เงินในลิ้นชัก (ตอนเปิดร้าน) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="opening_cash" value="0" required class="w-full border border-slate-300 p-3 rounded-lg text-right font-bold text-xl focus:ring-2 focus:ring-emerald-500">
                    <p class="text-xs text-slate-500 mt-1">เงินทอนเริ่มต้นที่มีในลิ้นชัก</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">หมายเหตุ</label>
                    <textarea name="opening_note" rows="3" class="w-full border border-slate-300 p-2 rounded-lg" placeholder="เช่น เงินทอนจากเมื่อวาน"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t bg-slate-50 flex justify-end gap-2">
                <button type="button" onclick="closeOpenDrawer()" class="bg-white border border-slate-300 text-slate-700 px-5 py-2 rounded-lg hover:bg-slate-100">ยกเลิก</button>
                <button type="submit" class="bg-emerald-600 text-white px-6 py-2 rounded-lg hover:bg-emerald-700 shadow">🔓 เปิดกะ</button>
            </div>
        </form>
    </div>

    {{-- ============== Drawer: Close Shift ============== --}}
    <div id="closeShiftBackdrop" onclick="closeCloseDrawer()" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm hidden transition-opacity"></div>
    <div id="closeShiftPanel" class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
        <div class="px-6 py-5 border-b bg-red-50 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-slate-800">🔒 ปิดกะ</h3>
                <p class="text-sm text-slate-500 mt-0.5">นับเงินในลิ้นชักและปิดกะ</p>
            </div>
            <button onclick="closeCloseDrawer()" class="text-slate-400 hover:text-slate-700 hover:bg-slate-200 w-9 h-9 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="closeShiftForm" method="POST" class="flex-1 flex flex-col overflow-hidden">
            @csrf
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <p class="text-sm text-slate-600">เงินทอนเริ่มต้น: <span class="font-bold text-slate-900" id="displayOpeningCash">฿0.00</span></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">เงินสดที่นับได้จริง (ในลิ้นชัก) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="closing_cash" id="closingCash" required class="w-full border border-slate-300 p-3 rounded-lg text-right font-bold text-xl focus:ring-2 focus:ring-red-500">
                    <p class="text-xs text-slate-500 mt-1">นับเงินจริงในลิ้นชัก ณ ตอนปิดกะ</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">หมายเหตุ</label>
                    <textarea name="closing_note" rows="3" class="w-full border border-slate-300 p-2 rounded-lg" placeholder="เช่น มีเงินขาด 50 บาท"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t bg-slate-50 flex justify-end gap-2">
                <button type="button" onclick="closeCloseDrawer()" class="bg-white border border-slate-300 text-slate-700 px-5 py-2 rounded-lg hover:bg-slate-100">ยกเลิก</button>
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 shadow">🔒 ปิดกะ</button>
            </div>
        </form>
    </div>

    {{-- JavaScript --}}
    <script>
        // Drawer: Open Shift
        const openBackdrop = document.getElementById('openShiftBackdrop');
        const openPanel = document.getElementById('openShiftPanel');

        function openOpenDrawer() {
            openBackdrop.classList.remove('hidden');
            setTimeout(() => openPanel.classList.remove('translate-x-full'), 10);
        }
        function closeOpenDrawer() {
            openPanel.classList.add('translate-x-full');
            setTimeout(() => openBackdrop.classList.add('hidden'), 300);
        }

        // Drawer: Close Shift
        const closeBackdrop = document.getElementById('closeShiftBackdrop');
        const closePanel = document.getElementById('closeShiftPanel');

        function openCloseDrawer(id, openingCash) {
            document.getElementById('closeShiftForm').action = `/cashier-shifts/${id}/close`;
            document.getElementById('displayOpeningCash').textContent = '฿' + openingCash.toFixed(2);
            closeBackdrop.classList.remove('hidden');
            setTimeout(() => closePanel.classList.remove('translate-x-full'), 10);
        }
        function closeCloseDrawer() {
            closePanel.classList.add('translate-x-full');
            setTimeout(() => closeBackdrop.classList.add('hidden'), 300);
        }

        // ปิดด้วย ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeOpenDrawer();
                closeCloseDrawer();
            }
        });
    </script>
@endsection
