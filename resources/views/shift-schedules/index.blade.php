@extends('layouts.app')
@section('title', 'ตารางกะพนักงาน')

@push('scripts')
    {{-- FullCalendar (CDN) + ภาษาไทย --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/th.global.min.js"></script>
@endpush

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">📅 ตารางกะพนักงาน</h1>
                <p class="text-sm text-slate-500 mt-1">กำหนดตารางเข้ากะล่วงหน้าสำหรับพนักงานแต่ละคน</p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Toggle Button --}}
                <div class="bg-slate-100 p-1 rounded-lg flex">
                    <button type="button" id="btnTableView" onclick="switchView('table')" class="px-4 py-1.5 rounded-md text-sm font-medium bg-white shadow text-slate-800 transition">
                        📋 ตาราง
                    </button>
                    <button type="button" id="btnCalendarView" onclick="switchView('calendar')" class="px-4 py-1.5 rounded-md text-sm font-medium text-slate-500 hover:text-slate-800 transition">
                        📅 ปฏิทิน
                    </button>
                </div>
                <button onclick="openAddDrawer()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 shadow transition flex items-center gap-2">
                    + เพิ่มตารางกะ
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-emerald-100 text-emerald-700 p-4 rounded-lg">{{ session('success') }}</div>
        @endif

        {{-- แสดง Validation Error --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- ============== TABLE VIEW ============== --}}
        <div id="tableView">
            <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="p-4 font-medium">พนักงาน</th>
                            <th class="p-4 font-medium">วันที่</th>
                            <th class="p-4 font-medium">เวลา</th>
                            <th class="p-4 font-medium">หมายเหตุ</th>
                            <th class="p-4 font-medium text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($schedules as $schedule)
                            <tr class="hover:bg-slate-50">
                                <td class="p-4 font-medium text-slate-800">{{ $schedule->user?->name ?? '-' }}</td>
                                <td class="p-4 text-slate-600">{{ $schedule->shift_date->format('d/m/Y') }}</td>
                                <td class="p-4 text-slate-600">
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                </td>
                                <td class="p-4 text-slate-500">{{ $schedule->note ?? '-' }}</td>
                                <td class="p-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="openEditDrawer({{ $schedule->id }})" class="text-blue-500 hover:bg-blue-50 p-2 rounded-lg transition" title="แก้ไข">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form action="{{ route('shift-schedules.destroy', $schedule->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition" title="ลบ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-400 italic">ยังไม่มีตารางกะ</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4 border-t">{{ $schedules->links() }}</div>
            </div>
        </div>

        {{-- ============== CALENDAR VIEW ============== --}}
        <div id="calendarView" class="hidden">
            <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 p-4">
                <div class="flex flex-wrap gap-4 text-xs text-slate-500 mb-3">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#10b981"></span> กะเช้า</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#f59e0b"></span> กะบ่าย</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#3b82f6"></span> อื่น ๆ</span>
                    <span class="ml-auto">คลิกวันว่างเพื่อเพิ่มกะ · คลิกกะเพื่อแก้ไข</span>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    {{-- ============== Drawer: Add / Edit Shift Schedule ============== --}}
    <div id="shiftBackdrop" onclick="closeDrawer()" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm hidden transition-opacity duration-300"></div>
    <div id="shiftPanel" class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <div>
                <h3 class="text-lg font-bold text-slate-800" id="drawerTitle">เพิ่มตารางกะ</h3>
                <p class="text-sm text-slate-500 mt-0.5" id="drawerSubtitle">กำหนดตารางเข้ากะล่วงหน้า</p>
            </div>
            <button onclick="closeDrawer()" class="text-slate-400 hover:text-slate-700 hover:bg-slate-200 w-9 h-9 rounded-lg flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Form --}}
        <form id="shiftForm" method="POST" class="flex-1 flex flex-col overflow-hidden">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="flex-1 overflow-y-auto p-6 space-y-5">
                {{-- พนักงาน --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">พนักงาน <span class="text-red-500">*</span></label>
                    <select name="user_id" id="fieldUserId" required class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- เลือกพนักงาน --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- วันที่ --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">วันที่ <span class="text-red-500">*</span></label>
                    <input type="date" name="shift_date" id="fieldShiftDate" value="{{ date('Y-m-d') }}" required class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- เวลา --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">เวลาเข้ากะ <span class="text-red-500">*</span></label>
                        <input type="time" name="start_time" id="fieldStartTime" value="08:00" required class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">เวลาเลิกกะ <span class="text-red-500">*</span></label>
                        <input type="time" name="end_time" id="fieldEndTime" value="17:00" required class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- หมายเหตุ --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">หมายเหตุ</label>
                    <input type="text" name="note" id="fieldNote" class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="เช่น กะเช้า, กะบ่าย">
                    <p class="text-xs text-slate-400 mt-1.5">พิมพ์ "กะเช้า" หรือ "กะบ่าย" เพื่อให้ปฏิทินแสดงสีตามกะ</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                <button type="button" onclick="closeDrawer()" class="bg-white border border-slate-300 text-slate-700 px-5 py-2.5 rounded-lg hover:bg-slate-100 font-medium">ยกเลิก</button>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 shadow font-medium">💾 บันทึก</button>
            </div>
        </form>
    </div>

    {{-- JavaScript --}}
    <script>
        const drawerBackdrop = document.getElementById('shiftBackdrop');
        const drawerPanel = document.getElementById('shiftPanel');
        const shiftForm = document.getElementById('shiftForm');
        const todayStr = '{{ date('Y-m-d') }}';

        // ---------- จำ view ที่เลือก (กัน localStorage ใช้ไม่ได้) ----------
        function saveView(view) {
            try { localStorage.setItem('shiftView', view); } catch (e) {}
        }
        function loadView() {
            try { return localStorage.getItem('shiftView') || 'table'; } catch (e) { return 'table'; }
        }

        // ---------- สลับ View ----------
        function switchView(view) {
            const isTable = view === 'table';
            const btnTable = document.getElementById('btnTableView');
            const btnCalendar = document.getElementById('btnCalendarView');
            const active = ['bg-white', 'shadow', 'text-slate-800'];
            const idle = ['text-slate-500', 'hover:text-slate-800'];

            document.getElementById('tableView').classList.toggle('hidden', !isTable);
            document.getElementById('calendarView').classList.toggle('hidden', isTable);

            btnTable.classList.remove(...active, ...idle);
            btnCalendar.classList.remove(...active, ...idle);
            btnTable.classList.add(...(isTable ? active : idle));
            btnCalendar.classList.add(...(isTable ? idle : active));

            saveView(view);

            if (!isTable) renderCalendar();
        }

        // ---------- FullCalendar ----------
        let calendarInstance = null;

        function renderCalendar() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl || typeof FullCalendar === 'undefined') return;

            // สร้างครั้งเดียว; สลับกลับมาแค่ปรับขนาด + โหลดข้อมูลใหม่
            if (calendarInstance) {
                calendarInstance.updateSize();
                calendarInstance.refetchEvents();
                return;
            }

            calendarInstance = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'th',
                firstDay: 1,
                height: 'auto',
                dayMaxEvents: 4,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,listWeek'
                },
                buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์', list: 'รายการ' },

                // ดึงข้อมูลตามช่วงวันที่ที่กำลังดู (ดูเดือนไหนก็มีข้อมูล)
                events: '{{ route('shift-schedules.events') }}',

                // คลิกวันว่าง → เพิ่มกะในวันนั้น
                dateClick: (info) => openAddDrawer(info.dateStr),

                // คลิกกะ → แก้ไข
                eventClick: (info) => {
                    info.jsEvent.preventDefault();
                    openEditDrawer(info.event.id);
                },
            });

            calendarInstance.render();
        }

        // ---------- Drawer ----------
        function openAddDrawer(dateStr) {
            shiftForm.reset();
            document.getElementById('fieldShiftDate').value = dateStr || todayStr;
            document.getElementById('fieldStartTime').value = '08:00';
            document.getElementById('fieldEndTime').value = '17:00';

            shiftForm.action = '{{ route('shift-schedules.store') }}';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('drawerTitle').textContent = 'เพิ่มตารางกะ';
            document.getElementById('drawerSubtitle').textContent = 'กำหนดตารางเข้ากะล่วงหน้า';

            showDrawer();
        }

        async function openEditDrawer(id) {
            try {
                const res = await fetch(`/shift-schedules/${id}/edit-data`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                const userSelect = document.getElementById('fieldUserId');

                // ถ้าพนักงานคนนี้ไม่อยู่ใน dropdown (เช่น ไม่ใช่ cashier แล้ว) ให้เพิ่มตัวเลือกชั่วคราว
                if (![...userSelect.options].some(o => o.value == data.user_id)) {
                    const opt = document.createElement('option');
                    opt.value = data.user_id;
                    opt.textContent = data.user_name ?? ('ผู้ใช้ #' + data.user_id);
                    userSelect.appendChild(opt);
                }

                userSelect.value = data.user_id;
                document.getElementById('fieldShiftDate').value = data.shift_date;
                document.getElementById('fieldStartTime').value = (data.start_time || '').substring(0, 5);
                document.getElementById('fieldEndTime').value = (data.end_time || '').substring(0, 5);
                document.getElementById('fieldNote').value = data.note ?? '';

                shiftForm.action = `/shift-schedules/${id}`;
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('drawerTitle').textContent = 'แก้ไขตารางกะ';
                document.getElementById('drawerSubtitle').textContent = 'อัปเดตข้อมูลกะ';

                showDrawer();
            } catch (e) {
                alert('ไม่สามารถโหลดข้อมูลได้');
                console.error(e);
            }
        }

        function showDrawer() {
            drawerBackdrop.classList.remove('hidden');
            setTimeout(() => drawerPanel.classList.remove('translate-x-full'), 10);
        }

        function closeDrawer() {
            drawerPanel.classList.add('translate-x-full');
            setTimeout(() => drawerBackdrop.classList.add('hidden'), 300);
        }

        // ปิดด้วย ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDrawer();
        });

        // โหลด View ที่เคยเลือกไว้
        document.addEventListener('DOMContentLoaded', () => switchView(loadView()));
    </script>
@endsection
