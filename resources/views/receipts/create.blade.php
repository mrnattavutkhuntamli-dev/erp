@extends('layouts.app')
@section('title', 'รับชำระเงิน')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">💰 เลือกบิลที่ต้องการรับชำระเงิน</h1>
            <a href="{{ route('receipts.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if (session('error')) <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg">{{ session('error') }}</div> @endif

        <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 text-sm">
                    <tr>
                        <th class="p-4 font-medium">เลขที่บิล</th>
                        <th class="p-4 font-medium">วันที่ขาย</th>
                        <th class="p-4 font-medium">ลูกค้า</th>
                        <th class="p-4 font-medium text-right">ยอดรวม</th>
                        <th class="p-4 font-medium text-right">ค้างชำระ</th>
                        <th class="p-4 font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($unpaidSales as $sale)
                        <tr class="hover:bg-slate-50">
                            <td class="p-4 font-medium text-blue-600">{{ $sale->invoice_no }}</td>
                            <td class="p-4 text-slate-600">{{ $sale->created_at->format('d/m/Y') }}</td>
                            <td class="p-4 text-slate-800">{{ $sale->customer->name ?? 'ลูกค้าทั่วไป' }}</td>
                            <td class="p-4 text-right text-slate-600">฿{{ number_format($sale->total, 2) }}</td>
                            <td class="p-4 text-right font-bold text-red-600">฿{{ number_format($sale->total - $sale->paid_amount, 2) }}</td>
                            <td class="p-4 text-center">
                                <a href="{{ route('receipts.show', 0) }}?sale_id={{ $sale->id }}" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-xs font-medium">รับชำระ</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-slate-400 italic">ไม่มีบิลที่ค้างชำระเงิน 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
