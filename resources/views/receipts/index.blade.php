@extends('layouts.app')
@section('title', 'ประวัติการรับชำระเงิน')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">ประวัติการรับชำระเงิน (Receipts)</h1>
        <a href="{{ route('receipts.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 shadow transition">+ รับชำระเงิน</a>
    </div>

    @if (session('success')) <div class="bg-emerald-100 text-emerald-700 p-4 mb-4 rounded-lg">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg">{{ session('error') }}</div> @endif

    <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 text-slate-500 text-sm">
                <tr>
                    <th class="p-4 font-medium">เลขที่ใบรับเงิน</th>
                    <th class="p-4 font-medium">วันที่รับเงิน</th>
                    <th class="p-4 font-medium">อ้างอิงบิล</th>
                    <th class="p-4 font-medium">ลูกค้า</th>
                    <th class="p-4 font-medium text-right">ยอดรับ</th>
                    <th class="p-4 font-medium text-center">สถานะ</th>
                    <th class="p-4 font-medium text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($receipts as $receipt)
                    <tr class="hover:bg-slate-50 {{ $receipt->status === 'voided' ? 'bg-red-50 opacity-60' : '' }}">
                        <td class="p-4 font-medium text-blue-600">
                            {{ $receipt->receipt_no }}
                            @if($receipt->status === 'voided')
                                <span class="text-red-500 text-xs block">(ยกเลิกแล้ว)</span>
                            @endif
                        </td>
                        <td class="p-4 text-slate-600">{{ $receipt->payment_date->format('d/m/Y') }}</td>
                        <td class="p-4 text-slate-600">{{ $receipt->sale->invoice_no ?? '-' }}</td>
                        <td class="p-4 text-slate-800">{{ $receipt->sale->customer->name ?? 'ลูกค้าทั่วไป' }}</td>
                        <td class="p-4 text-right font-bold text-emerald-600">฿{{ number_format($receipt->amount, 2) }}</td>
                        <td class="p-4 text-center">
                            @if($receipt->status === 'voided')
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">ยกเลิกแล้ว</span>
                            @else
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">ใช้งาน</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            <a href="{{ route('receipts.show', $receipt->id) }}" class="bg-blue-100 text-blue-600 px-3 py-1 rounded hover:bg-blue-200 text-xs font-medium">ดูรายละเอียด</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-slate-400 italic">ยังไม่มีประวัติการรับชำระเงิน</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-200">{{ $receipts->links() }}</div>
    </div>
@endsection
