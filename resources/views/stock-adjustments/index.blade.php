@extends('layouts.app')
@section('title', 'ปรับปรุงสต็อก')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">ประวัติการปรับปรุงสต็อก</h1>
        <a href="{{ route('stock-adjustments.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 shadow transition">+ ปรับปรุงสต็อก</a>
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
                    <th class="p-4 font-medium">เลขที่เอกสาร</th>
                    <th class="p-4 font-medium">วันที่</th>
                    <th class="p-4 font-medium">สินค้า</th>
                    <th class="p-4 font-medium text-center">ประเภท</th>
                    <th class="p-4 font-medium text-center">จำนวน</th>
                    <th class="p-4 font-medium text-center">สต็อกก่อน → หลัง</th>
                    <th class="p-4 font-medium">เหตุผล</th>
                    <th class="p-4 font-medium">พนักงาน</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($adjustments as $adj)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-medium text-blue-600">{{ $adj->adj_no }}</td>
                        <td class="p-4 text-slate-600">{{ $adj->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-4">
                            <div class="font-medium text-slate-800">{{ $adj->product->name }}</div>
                            <div class="text-xs text-slate-500">{{ $adj->product->sku }}</div>
                        </td>
                        <td class="p-4 text-center">
                            @if($adj->type === 'addition')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">+ เพิ่ม</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">- ลด</span>
                            @endif
                        </td>
                        <td class="p-4 text-center font-bold {{ $adj->type === 'addition' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $adj->type === 'addition' ? '+' : '-' }}{{ number_format($adj->quantity) }}
                        </td>
                        <td class="p-4 text-center text-slate-700">
                            {{ number_format($adj->stock_before) }} → <span class="font-bold">{{ number_format($adj->stock_after) }}</span>
                        </td>
                        <td class="p-4">
                            @php
                                $reasons = [
                                    'damaged' => 'ชำรุดเสียหาย',
                                    'lost' => 'สูญหาย',
                                    'stocktake' => 'นับสต็อกจริง',
                                    'return' => 'คืนสินค้า',
                                    'other' => 'อื่นๆ'
                                ];
                            @endphp
                            <span class="text-slate-600">{{ $reasons[$adj->reason] ?? $adj->reason }}</span>
                        </td>
                        <td class="p-4 text-slate-600">{{ $adj->user->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-8 text-center text-slate-400 italic">ยังไม่มีประวัติการปรับปรุงสต็อก</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-200">{{ $adjustments->links() }}</div>
    </div>
@endsection
