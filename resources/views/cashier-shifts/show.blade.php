@extends('layouts.app')
@section('title', 'สรุปกะ #' . $cashierShift->id)

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">🧾 สรุปกะ #{{ $cashierShift->id }}</h1>
                <p class="text-sm text-slate-500 mt-1">พนักงาน: {{ $cashierShift->user->name ?? '-' }}</p>
            </div>
            <a href="{{ route('cashier-shifts.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← กลับ</a>
        </div>

        @if (session('success'))
            <div class="bg-emerald-100 text-emerald-700 p-4 rounded-lg border border-emerald-200">{{ session('success') }}</div>
        @endif

        <div class="bg-white shadow ring-1 ring-slate-200 rounded-xl p-6 space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">สถานะ</span>
                <span class="font-semibold">{{ $cashierShift->status === 'open' ? 'เปิดอยู่' : 'ปิดแล้ว' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">เปิดกะ</span>
                <span>{{ $cashierShift->opened_at?->format('d/m/Y H:i') }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">ปิดกะ</span>
                <span>{{ $cashierShift->closed_at?->format('d/m/Y H:i') ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">จำนวนบิล</span>
                <span>{{ number_format($cashierShift->total_bills) }}</span></div>
        </div>

        <div class="bg-white shadow ring-1 ring-slate-200 rounded-xl p-6 space-y-3 text-sm">
            <h2 class="font-bold text-slate-800">ยอดขายแยกตามวิธีชำระ</h2>
            <div class="flex justify-between"><span class="text-slate-500">เงินสด</span><span>฿{{ number_format($cashierShift->total_cash_sales, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">โอนเงิน</span><span>฿{{ number_format($cashierShift->total_transfer_sales, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">บัตรเครดิต</span><span>฿{{ number_format($cashierShift->total_card_sales, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">ขายเชื่อ</span><span>฿{{ number_format($cashierShift->total_credit_sales, 2) }}</span></div>
            <div class="flex justify-between border-t pt-3 font-bold"><span>ยอดขายรวม</span><span>฿{{ number_format($cashierShift->total_sales, 2) }}</span></div>
        </div>

        <div class="bg-white shadow ring-1 ring-slate-200 rounded-xl p-6 space-y-3 text-sm">
            <h2 class="font-bold text-slate-800">เงินในลิ้นชัก</h2>
            <div class="flex justify-between"><span class="text-slate-500">เงินทอนเริ่มต้น</span><span>฿{{ number_format($cashierShift->opening_cash, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">+ รับชำระหนี้เป็นเงินสด</span><span>฿{{ number_format($cashierShift->total_cash_receipts, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">ควรมีในลิ้นชัก</span><span>฿{{ number_format($expectedCash, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">นับได้จริง</span>
                <span>{{ $cashierShift->closing_cash !== null ? '฿' . number_format($cashierShift->closing_cash, 2) : '-' }}</span></div>
            @if($cashierShift->closing_cash !== null)
                <div class="flex justify-between border-t pt-3 font-bold {{ abs($difference) < 0.005 ? 'text-emerald-600' : 'text-red-600' }}">
                    <span>ผลต่าง</span><span>{{ $difference > 0 ? '+' : '' }}฿{{ number_format($difference, 2) }}</span>
                </div>
            @endif
            @if($cashierShift->opening_note)<p class="text-slate-500">หมายเหตุเปิดกะ: {{ $cashierShift->opening_note }}</p>@endif
            @if($cashierShift->closing_note)<p class="text-slate-500">หมายเหตุปิดกะ: {{ $cashierShift->closing_note }}</p>@endif
        </div>
    </div>
@endsection
