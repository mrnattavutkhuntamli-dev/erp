@extends('layouts.app')
@section('title', 'ใบเสนอราคา')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">รายการใบเสนอราคา</h1>
        <a href="{{ route('quotations.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 shadow transition">+ สร้างใบเสนอราคา</a>
    </div>

    @if (session('success')) <div class="bg-emerald-100 text-emerald-700 p-4 mb-4 rounded-lg">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg">{{ session('error') }}</div> @endif

    <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 text-slate-500 text-sm">
                <tr>
                    <th class="p-4 font-medium">เลขที่</th>
                    <th class="p-4 font-medium">วันที่</th>
                    <th class="p-4 font-medium">ลูกค้า</th>
                    <th class="p-4 font-medium text-right">ยอดรวม</th>
                    <th class="p-4 font-medium text-center">สถานะ</th>
                    <th class="p-4 font-medium text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse($quotations as $qt)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-medium text-blue-600">{{ $qt->quote_no }}</td>
                        <td class="p-4 text-slate-600">{{ $qt->quote_date->format('d/m/Y') }}</td>
                        <td class="p-4 text-slate-800">{{ $qt->customer->name ?? 'ลูกค้าทั่วไป' }}</td>
                        <td class="p-4 text-right font-bold">฿{{ number_format($qt->total, 2) }}</td>
                        <td class="p-4 text-center">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-slate-100 text-slate-700',
                                    'sent' => 'bg-blue-100 text-blue-700',
                                    'accepted' => 'bg-green-100 text-green-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    'expired' => 'bg-yellow-100 text-yellow-700',
                                ];
                                $statusLabels = [
                                    'draft' => 'ร่าง',
                                    'sent' => 'ส่งแล้ว',
                                    'accepted' => 'อนุมัติ (แปลงบิลแล้ว)',
                                    'rejected' => 'ปฏิเสธ',
                                    'expired' => 'หมดอายุ',
                                ];
                            @endphp
                            <span class="{{ $statusColors[$qt->status] }} px-2 py-1 rounded text-xs font-bold">
                                {{ $statusLabels[$qt->status] }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <a href="{{ route('quotations.show', $qt->id) }}" class="bg-blue-100 text-blue-600 px-3 py-1 rounded hover:bg-blue-200 text-xs font-medium">ดูรายละเอียด</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-slate-400">ยังไม่มีใบเสนอราคา</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $quotations->links() }}</div>
    </div>
@endsection
