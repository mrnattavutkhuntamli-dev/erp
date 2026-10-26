@extends('layouts.app')
@section('title', 'ค่าใช้จ่าย')

@php
    $categories = [
        'rent'      => ['label' => 'ค่าเช่า',   'badge' => 'bg-violet-50 text-violet-700 ring-violet-200', 'dot' => 'bg-violet-500'],
        'utilities' => ['label' => 'ค่าน้ำ-ไฟ', 'badge' => 'bg-sky-50 text-sky-700 ring-sky-200',          'dot' => 'bg-sky-500'],
        'salary'    => ['label' => 'เงินเดือน', 'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'supplies'  => ['label' => 'ของใช้',    'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',    'dot' => 'bg-amber-500'],
        'other'     => ['label' => 'อื่นๆ',     'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',   'dot' => 'bg-slate-400'],
    ];
    $fallbackCat = ['label' => null, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];

    $selMonth = request('month');
    $selYear  = request('year', now()->year);
    $selCat   = request('category');
    $hasFilter = request()->hasAny(['month', 'year', 'category']);

    $monthName = fn ($m) => \Carbon\Carbon::create(2000, (int) $m, 1)->locale('th')->monthName;

    $periodText = ($selMonth ? $monthName($selMonth) . ' ' : 'ทั้งปี ') . $selYear;
    $periodText .= $selCat && isset($categories[$selCat]) ? ' · ' . $categories[$selCat]['label'] : '';

    $input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm
              focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30';
    $field = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800 shadow-sm
              focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30';
@endphp

@section('content')
<div class="w-full space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">ค่าใช้จ่าย</h1>
            <p class="mt-1 text-sm text-slate-500">บันทึกรายจ่ายของร้าน เช่น ค่าเช่า ค่าไฟ เงินเดือน</p>
        </div>
        <button type="button" onclick="openAddDrawer()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 4a1 1 0 011 1v4h4a1 1 0 110 2h-4v4a1 1 0 11-2 0v-4H5a1 1 0 110-2h4V5a1 1 0 011-1z"/></svg>
            เพิ่มค่าใช้จ่าย
        </button>
    </div>

    {{-- Flash / errors --}}
    @if (session('success'))
        <div id="flash" class="flex items-start gap-3 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.58 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="flex-1">{{ session('success') }}</span>
            <button type="button" onclick="document.getElementById('flash').remove()" class="text-emerald-600 hover:text-emerald-800" aria-label="ปิด">&times;</button>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
            <p class="font-medium">บันทึกไม่สำเร็จ</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Summary + Filter --}}
    <div class="grid gap-5 lg:grid-cols-3">
        {{-- ยอดรวม --}}
        <div class="relative overflow-hidden rounded-2xl bg-slate-900 p-6 text-white shadow-sm lg:col-span-1">
            <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-rose-500/20" aria-hidden="true"></div>
            <p class="text-sm text-slate-300">ยอดรวมค่าใช้จ่าย</p>
            <p class="mt-2 text-4xl font-bold tabular-nums tracking-tight">
                <span class="text-2xl font-medium text-slate-400">฿</span>{{ number_format($totalAmount, 2) }}
            </p>
            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full bg-white/10 px-2.5 py-1">{{ $periodText }}</span>
                <span class="rounded-full bg-white/10 px-2.5 py-1">{{ number_format($expenses->total()) }} รายการ</span>
            </div>
        </div>

        {{-- Filter --}}
        <form action="{{ route('expenses.index') }}" method="GET"
              class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div class="sm:col-span-2">
                    <label for="month" class="mb-1.5 block text-xs font-medium text-slate-600">เดือน</label>
                    <select id="month" name="month" class="{{ $input }}">
                        <option value="">ทั้งปี</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" @selected($selMonth == $i)>{{ $monthName($i) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="sm:col-span-1">
                    <label for="year" class="mb-1.5 block text-xs font-medium text-slate-600">ปี</label>
                    <select id="year" name="year" class="{{ $input }}">
                        @for($i = now()->year; $i >= now()->year - 3; $i--)
                            <option value="{{ $i }}" @selected($selYear == $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-span-2 flex items-end gap-2 sm:col-span-2">
                    <button type="submit"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.48l4.31 4.3a1 1 0 01-1.4 1.42l-4.32-4.31A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                        กรองข้อมูล
                    </button>
                    @if($hasFilter)
                        <a href="{{ route('expenses.index') }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 transition hover:bg-slate-100">ล้าง</a>
                    @endif
                </div>
            </div>
            @if($selCat) <input type="hidden" name="category" value="{{ $selCat }}"> @endif

            {{-- ประเภท (กดกรองได้ทันที) --}}
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 text-sm">
                <span class="text-slate-500">ประเภท</span>
                @php $base = request()->except(['page', 'category']); @endphp
                <a href="{{ route('expenses.index', $base) }}"
                   class="rounded-full px-3 py-1 text-xs font-medium ring-1 transition {{ !$selCat ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' }}">
                    ทั้งหมด
                </a>
                @foreach($categories as $key => $c)
                    <a href="{{ route('expenses.index', array_merge($base, ['category' => $key])) }}"
                       class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 transition {{ $selCat === $key ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' }}">
                        <span class="h-2 w-2 rounded-full {{ $c['dot'] }}"></span>{{ $c['label'] }}
                    </a>
                @endforeach
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

        {{-- Desktop --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full border-collapse text-left text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500">
                    <tr class="border-b border-slate-200">
                        <th class="px-4 py-3 font-medium">วันที่</th>
                        <th class="px-4 py-3 font-medium">ประเภท</th>
                        <th class="px-4 py-3 font-medium">รายละเอียด</th>
                        <th class="px-4 py-3 text-right font-medium">จำนวนเงิน</th>
                        <th class="px-4 py-3 font-medium">ผู้บันทึก</th>
                        <th class="px-4 py-3 text-right font-medium">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $expense)
                        @php $c = $categories[$expense->category] ?? $fallbackCat; @endphp
                        <tr class="group transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $c['badge'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $c['dot'] }}"></span>{{ $c['label'] ?? $expense->category }}
                                </span>
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 font-medium text-slate-800" title="{{ $expense->description }}">{{ $expense->description }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-rose-600">฿{{ number_format($expense->amount, 2) }}</td>
                            <td class="px-4 py-3 text-slate-500">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-medium text-slate-600">
                                        {{ mb_substr($expense->user->name ?? '?', 0, 1) }}
                                    </span>
                                    {{ $expense->user->name ?? '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <button type="button" onclick="openEditDrawer({{ $expense->id }})"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" title="แก้ไข" aria-label="แก้ไข">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500" title="ลบ" aria-label="ลบ">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                                    <svg class="h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                                    <p class="font-medium text-slate-700">{{ $hasFilter ? 'ไม่พบค่าใช้จ่ายตามเงื่อนไข' : 'ยังไม่มีค่าใช้จ่าย' }}</p>
                                    <p class="text-sm text-slate-500">{{ $hasFilter ? 'ลองเปลี่ยนเดือน ปี หรือประเภท' : 'กด "เพิ่มค่าใช้จ่าย" เพื่อบันทึกรายการแรก' }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <ul class="divide-y divide-slate-100 md:hidden">
            @forelse($expenses as $expense)
                @php $c = $categories[$expense->category] ?? $fallbackCat; @endphp
                <li class="px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate font-medium text-slate-800">{{ $expense->description }}</div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 font-medium ring-1 ring-inset {{ $c['badge'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $c['dot'] }}"></span>{{ $c['label'] ?? $expense->category }}
                                </span>
                                <span>{{ $expense->expense_date->format('d/m/Y') }}</span>
                                <span>· {{ $expense->user->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="shrink-0 text-right font-semibold tabular-nums text-rose-600">฿{{ number_format($expense->amount, 2) }}</div>
                    </div>
                    <div class="mt-2 flex justify-end gap-1">
                        <button type="button" onclick="openEditDrawer({{ $expense->id }})" class="rounded-lg px-3 py-1.5 text-xs font-medium text-blue-600 ring-1 ring-blue-200 hover:bg-blue-50">แก้ไข</button>
                        <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 ring-1 ring-red-200 hover:bg-red-50">ลบ</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center">
                    <p class="font-medium text-slate-700">{{ $hasFilter ? 'ไม่พบค่าใช้จ่ายตามเงื่อนไข' : 'ยังไม่มีค่าใช้จ่าย' }}</p>
                </li>
            @endforelse
        </ul>

        @if($expenses->hasPages())
            <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/60 px-4 py-3 sm:flex-row">
                <p class="text-xs text-slate-500">
                    แสดง {{ number_format($expenses->firstItem()) }}-{{ number_format($expenses->lastItem()) }}
                    จาก {{ number_format($expenses->total()) }} รายการ
                </p>
                {{ $expenses->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ============== Drawer: Add / Edit Expense ============== --}}
<div id="expenseBackdrop" onclick="closeDrawer()" class="fixed inset-0 z-40 hidden bg-slate-900/50 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>

<div id="expensePanel" role="dialog" aria-modal="true" aria-labelledby="drawerTitle"
     class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md translate-x-full transform flex-col bg-white shadow-2xl transition-transform duration-300 ease-in-out">

    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
        <div>
            <h3 class="text-lg font-bold text-slate-900" id="drawerTitle">เพิ่มค่าใช้จ่าย</h3>
            <p class="mt-0.5 text-sm text-slate-500" id="drawerSubtitle">บันทึกรายจ่ายใหม่</p>
        </div>
        <button type="button" onclick="closeDrawer()" aria-label="ปิด"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <form id="expenseForm" method="POST" class="flex flex-1 flex-col overflow-hidden">
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="flex-1 space-y-6 overflow-y-auto p-6">
            {{-- จำนวนเงิน (เด่นสุด) --}}
            <div>
                <label for="fieldAmount" class="mb-2 block text-sm font-medium text-slate-700">จำนวนเงิน <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-2xl font-medium text-slate-400">฿</span>
                    <input type="number" step="0.01" min="0" inputmode="decimal" name="amount" id="fieldAmount" required placeholder="0.00"
                           class="w-full rounded-xl border border-slate-300 py-4 pl-11 pr-4 text-right text-3xl font-bold tabular-nums text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>
            </div>

            {{-- ประเภท --}}
            <fieldset>
                <legend class="mb-2 text-sm font-medium text-slate-700">ประเภท <span class="text-red-500">*</span></legend>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($categories as $key => $c)
                        <label class="cursor-pointer {{ $loop->last ? 'col-span-2' : '' }}">
                            <input type="radio" name="category" value="{{ $key }}" required class="peer sr-only">
                            <span class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-checked:ring-1 peer-checked:ring-blue-500 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500">
                                <span class="h-2.5 w-2.5 rounded-full {{ $c['dot'] }}"></span>{{ $c['label'] }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            {{-- รายละเอียด --}}
            <div>
                <label for="fieldDescription" class="mb-2 block text-sm font-medium text-slate-700">รายละเอียด <span class="text-red-500">*</span></label>
                <input type="text" name="description" id="fieldDescription" required maxlength="255" placeholder="เช่น ค่าไฟเดือนตุลาคม" class="{{ $field }}">
            </div>

            {{-- วันที่ --}}
            <div>
                <label for="fieldExpenseDate" class="mb-2 block text-sm font-medium text-slate-700">วันที่จ่าย <span class="text-red-500">*</span></label>
                <input type="date" name="expense_date" id="fieldExpenseDate" value="{{ date('Y-m-d') }}" required class="{{ $field }}">
            </div>

            <div id="errorBox" class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" onclick="closeDrawer()"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100">ยกเลิก</button>
            <button type="submit" id="submitBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                บันทึก
            </button>
        </div>
    </form>
</div>

<script>
    const drawerBackdrop = document.getElementById('expenseBackdrop');
    const drawerPanel    = document.getElementById('expensePanel');
    const expenseForm    = document.getElementById('expenseForm');
    const submitBtn      = document.getElementById('submitBtn');
    let closeTimer = null;

    function setDrawerText(title, subtitle) {
        document.getElementById('drawerTitle').textContent = title;
        document.getElementById('drawerSubtitle').textContent = subtitle;
        document.getElementById('errorBox').classList.add('hidden');
    }

    function openAddDrawer() {
        expenseForm.reset();
        document.getElementById('fieldExpenseDate').value = '{{ date('Y-m-d') }}';
        expenseForm.action = '{{ route('expenses.store') }}';
        document.getElementById('formMethod').value = 'POST';
        setDrawerText('เพิ่มค่าใช้จ่าย', 'บันทึกรายจ่ายใหม่');
        showDrawer();
    }

    async function openEditDrawer(id) {
        try {
            const res = await fetch(`/expenses/${id}/edit-data`, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error(res.status);
            const data = await res.json();

            document.getElementById('fieldExpenseDate').value = data.expense_date;
            document.getElementById('fieldDescription').value = data.description;
            document.getElementById('fieldAmount').value = data.amount;
            const radio = expenseForm.querySelector(`input[name="category"][value="${data.category}"]`);
            if (radio) radio.checked = true;

            expenseForm.action = `/expenses/${id}`;
            document.getElementById('formMethod').value = 'PUT';
            setDrawerText('แก้ไขค่าใช้จ่าย', 'อัปเดตข้อมูลรายจ่าย');
            showDrawer();
        } catch (e) {
            alert('ไม่สามารถโหลดข้อมูลได้');
            console.error(e);
        }
    }

    function showDrawer() {
        clearTimeout(closeTimer);
        submitBtn.disabled = false;
        submitBtn.textContent = 'บันทึก';
        drawerBackdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => {
            drawerBackdrop.classList.remove('opacity-0');
            drawerPanel.classList.remove('translate-x-full');
        });
        setTimeout(() => document.getElementById('fieldAmount').focus(), 320);
    }

    function closeDrawer() {
        drawerPanel.classList.add('translate-x-full');
        drawerBackdrop.classList.add('opacity-0');
        document.body.classList.remove('overflow-hidden');
        closeTimer = setTimeout(() => drawerBackdrop.classList.add('hidden'), 300);
    }

    // กันกดบันทึกซ้ำ
    expenseForm.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = 'กำลังบันทึก...';
    });

    // ปิดด้วย ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDrawer();
    });
</script>
@endsection
