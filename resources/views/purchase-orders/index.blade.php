@extends('layouts.app')
@section('title', 'ใบสั่งซื้อ (PO)')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">รายการใบสั่งซื้อ</h1>
        <a href="{{ route('purchase-orders.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 shadow transition">+ สร้างใบสั่งซื้อ</a>
    </div>

    @if (session('success'))
        <div class="bg-emerald-100 text-emerald-700 p-4 mb-4 rounded-lg border border-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg border border-red-200">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 text-slate-500 text-sm">
                <tr>
                    <th class="p-4 font-medium">เลขที่ PO</th>
                    <th class="p-4 font-medium">วันที่สั่ง</th>
                    <th class="p-4 font-medium">ซัพพลายเออร์</th>
                    <th class="p-4 font-medium text-right">ยอดรวม</th>
                    <th class="p-4 font-medium text-center">สถานะ</th>
                    <th class="p-4 font-medium text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($purchaseOrders as $po)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-medium text-blue-600">{{ $po->po_no }}</td>
                        <td class="p-4 text-slate-600">{{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }}</td>
                        <td class="p-4 text-slate-800">{{ $po->supplier->name }}</td>
                        <td class="p-4 text-right font-bold">฿{{ number_format($po->total_amount, 2) }}</td>
                        <td class="p-4 text-center">
                            @if($po->status === 'pending')
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">รอรับของ</span>
                            @elseif($po->status === 'received')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">รับของแล้ว</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">ยกเลิก</span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            <a href="{{ route('purchase-orders.show', $po->id) }}" class="bg-blue-100 text-blue-600 px-3 py-1 rounded hover:bg-blue-200 text-xs font-medium">ดูรายละเอียด</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-slate-400">ยังไม่มีใบสั่งซื้อ</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-200">{{ $purchaseOrders->links() }}</div>
    </div>
@endsection
