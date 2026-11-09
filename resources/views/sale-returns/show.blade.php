@extends('layouts.app')

@section('title', 'ใบรับคืน ' . $return->return_no)

@section('header_actions')
    <a href="{{ route('sale-returns.index') }}"
       class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
        ← รายการใบรับคืน
    </a>
    <button type="button" onclick="window.print()"
            class="inline-flex items-center rounded-lg bg-ink-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-ink-800">
        พิมพ์
    </button>
@endsection

@section('content')
@php $voided = $return->status !== 'completed'; @endphp
<div class="mx-auto max-w-4xl space-y-5">

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-sm text-slate-500">ใบรับคืนสินค้า</div>
                <div class="text-xl font-semibold text-slate-900">{{ $return->return_no }}</div>
            </div>
            @if ($voided)
                <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-700">ยกเลิกแล้ว</span>
            @else
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">สำเร็จ</span>
            @endif
        </div>

        <dl class="mt-4 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
            <div class="flex justify-between sm:block"><dt class="text-slate-500">วันที่/เวลา</dt><dd class="text-slate-900">{{ $return->created_at->format('d/m/Y H:i') }}</dd></div>
            <div class="flex justify-between sm:block"><dt class="text-slate-500">บิลอ้างอิง</dt><dd class="text-slate-900">{{ $return->sale->invoice_no ?? '-' }}</dd></div>
            <div class="flex justify-between sm:block"><dt class="text-slate-500">ลูกค้า</dt><dd class="text-slate-900">{{ $return->sale->customer->name ?? 'ลูกค้าทั่วไป' }}</dd></div>
            <div class="flex justify-between sm:block"><dt class="text-slate-500">พนักงานที่บันทึก</dt><dd class="text-slate-900">{{ $return->user->name ?? '-' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">เหตุผล</dt><dd class="text-slate-900">{{ $return->reason }}</dd></div>
            @if ($voided && $return->voidedBy)
                <div class="sm:col-span-2"><dt class="text-slate-500">ยกเลิกโดย</dt><dd class="text-red-700">{{ $return->voidedBy->name }}@if ($return->voided_at) เมื่อ {{ $return->voided_at->format('d/m/Y H:i') }}@endif</dd></div>
            @endif
            @if ($voided && $return->void_reason)
                <div class="sm:col-span-2"><dt class="text-slate-500">เหตุผลที่ยกเลิก</dt><dd class="text-red-700">{{ $return->void_reason }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">สินค้า</th>
                    <th class="px-4 py-3 text-right font-medium">จำนวน</th>
                    <th class="px-4 py-3 text-right font-medium">มูลค่า</th>
                    <th class="px-4 py-3 text-center font-medium">คืนเข้าสต็อก</th>
                    <th class="px-4 py-3 text-right font-medium">สต็อกหลังคืน</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($return->items as $item)
                    <tr>
                        <td class="px-4 py-3">{{ $item->product->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right">฿{{ number_format($item->value, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($item->restock)
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">คืนแล้ว</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">ไม่คืน</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ $item->stock_after ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 text-sm">
                <tr>
                    <td colspan="2" class="px-4 py-3 text-right font-medium text-slate-600">มูลค่ารับคืนรวม</td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-900">฿{{ number_format($return->total_value, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="2" class="px-4 py-3 text-right font-medium text-slate-600">
                        คืนเงินลูกค้า @if ($return->refund_method) ({{ $return->refund_method === 'cash' ? 'เงินสด' : 'โอนเงิน' }}) @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-900">฿{{ number_format($return->refund_amount, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ยกเลิกใบรับคืน (เฉพาะผู้มีสิทธิ์ยกเลิกบิล) --}}
    @if (! $voided)
        @can('void-sale')
            <form method="POST" action="{{ route('sale-returns.destroy', $return->id) }}"
                  onsubmit="return confirm('ยืนยันยกเลิกใบรับคืนนี้? สต็อกที่รับคืนเข้าจะถูกตัดออก และยอดบิลจะกลับไปเหมือนก่อนรับคืน')"
                  class="space-y-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-red-200 print:hidden">
                @csrf
                @method('DELETE')
                <div class="text-sm font-semibold text-red-700">ยกเลิกใบรับคืน</div>
                <input type="text" name="void_reason" maxlength="500" required value="{{ old('void_reason') }}"
                       placeholder="เหตุผลที่ยกเลิก *"
                       class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500">
                @error('void_reason')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                    ยกเลิกใบรับคืน
                </button>
            </form>
        @endcan
    @endif
</div>
@endsection
