@extends('layouts.app')

@section('title', 'รับคืนสินค้า')

@section('header_actions')
    {{-- แก้ 1: ลิงก์กลับไปใบเสร็จ --}}
    <a href="{{ route('sales.show', $sale->id) }}"
       class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
        ← กลับไปใบเสร็จ
    </a>
@endsection

@section('content')
<div class="mx-auto max-w-5xl">

    <div class="mb-5 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="text-sm text-slate-500">บิลขาย</div>
        {{-- แก้ 2: แสดงเลขที่ใบเสร็จ ตรงกับหน้าประวัติการขาย --}}
        <div class="text-lg font-semibold text-slate-900">{{ $sale->invoice_no }}</div>
        <div class="mt-2 flex flex-wrap gap-x-8 gap-y-1 text-sm text-slate-600">
            <span>ลูกค้า: {{ $sale->customer->name ?? 'ลูกค้าทั่วไป' }}</span>
            <span>ยอดบิล: ฿{{ number_format($sale->total, 2) }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('sale-returns.store', $sale) }}" class="space-y-5">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">สินค้า</th>
                        <th class="px-4 py-3 text-right font-medium">ขาย</th>
                        <th class="px-4 py-3 text-right font-medium">คืนแล้ว</th>
                        <th class="w-36 px-4 py-3 text-left font-medium">จำนวนที่คืน</th>
                        <th class="px-4 py-3 text-center font-medium">คืนเข้าสต็อก</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($sale->items as $i => $item)
                        @php
                            $already = (int) ($returnedQty[$item->id] ?? 0);
                            $left    = $item->quantity - $already;
                        @endphp
                        <tr class="{{ $left <= 0 ? 'bg-slate-50 text-slate-400' : '' }}">
                            <td class="px-4 py-3">
                                {{ $item->product->name ?? '-' }}
                                <input type="hidden" name="items[{{ $i }}][sale_item_id]" value="{{ $item->id }}">
                            </td>
                            <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right">{{ $already }}</td>
                            <td class="px-4 py-3">
                                <input type="number" name="items[{{ $i }}][quantity]"
                                       value="{{ $left <= 0 ? 0 : old("items.$i.quantity", 0) }}"
                                       min="0" max="{{ $left }}"
                                       @readonly($left <= 0)
                                       class="w-full rounded-lg border-slate-300 px-3 py-1.5 text-sm focus:border-amber-500 focus:ring-amber-500 read-only:bg-slate-100">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="hidden" name="items[{{ $i }}][restock]" value="0">
                                <input type="checkbox" name="items[{{ $i }}][restock]" value="1"
                                       @checked(old("items.$i.restock", true)) @disabled($left <= 0)
                                       class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">วิธีคืนเงิน (ถ้ามีเงินต้องคืน)</label>
                <select name="refund_method"
                        class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">-- ไม่ระบุ --</option>
                    <option value="cash" @selected(old('refund_method') === 'cash')>เงินสด</option>
                    <option value="transfer" @selected(old('refund_method') === 'transfer')>โอนเงิน</option>
                </select>
                {{-- แก้ 3: คำอธิบาย (ไม่บังคับ) --}}
                <p class="mt-1 text-xs text-slate-500">
                    บิลเงินเชื่อที่ยังไม่ชำระ ไม่ต้องเลือก ระบบจะลดยอดค้างให้เอง
                </p>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">
                    เหตุผล <span class="text-red-500">*</span>
                </label>
                <input type="text" name="reason" maxlength="500" required value="{{ old('reason') }}"
                       class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="rounded-lg bg-amber-500 px-5 py-2 text-sm font-semibold text-ink-950 hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                บันทึกรับคืน
            </button>
            <a href="{{ route('sales.show', $sale->id) }}"
               class="rounded-lg border border-slate-200 bg-white px-5 py-2 text-sm text-slate-700 hover:bg-slate-50">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
