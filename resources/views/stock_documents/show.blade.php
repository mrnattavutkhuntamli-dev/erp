@extends('layouts.app')
@section('title', 'รายละเอียดเอกสาร')
@section('content')
    <div class="max-w-4xl mx-auto">
        <!-- ปุ่มควบคุม -->
        <div class="flex justify-between items-center mb-6">
            <a href="{{ route('stock-documents.index') }}" class="text-blue-600 hover:underline">&larr; กลับไปหน้ารายการ</a>
            <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded shadow hover:bg-gray-900">🖨️
                พิมพ์เอกสาร</button>
        </div>

        <!-- กระดาษเอกสาร (สำหรับพิมพ์) -->
        <div class="bg-white p-10 rounded shadow-md border border-gray-200 print:shadow-none print:border-none">
            <!-- Header เอกสาร -->
            <div class="flex justify-between items-start border-b-2 border-gray-800 pb-6 mb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">🚗 AutoPart ERP</h1>
                    <p class="text-gray-500 text-sm mt-1">ร้านขายอะไหล่รถยนต์ครบวงจร</p>
                    <p class="text-gray-500 text-sm">123 ถนนสุขุมวิท กรุงเทพฯ | โทร: 02-123-4567</p>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold {{ $stockDocument->type == 'in' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $stockDocument->type == 'in' ? 'ใบรับสินค้า' : 'ใบเบิกสินค้า' }}
                    </h2>
                    <p class="text-gray-700 font-medium mt-1">เลขที่: {{ $stockDocument->doc_no }}</p>
                    <p class="text-gray-600 text-sm">วันที่:
                        {{ \Carbon\Carbon::parse($stockDocument->date)->format('d/m/Y') }}</p>
                </div>
            </div>

            <!-- ข้อมูลทั่วไป -->
            <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
                <div>
                    <p class="text-gray-500">ผู้ทำรายการ:</p>
                    <p class="font-medium text-gray-800">{{ $stockDocument->user->name }}</p>
                </div>
                <div>
                    <p class="text-gray-500">{{ $stockDocument->type == 'in' ? 'ผู้ส่งของ:' : 'ผู้รับของ:' }}</p>
                    <p class="font-medium text-gray-800">
                        @if ($stockDocument->type == 'in')
                            {{ $stockDocument->supplier->name ?? '-' }}
                        @else
                            {{ $stockDocument->receiver_name ?? '-' }}
                        @endif
                    </p>
                </div>
                <div class="col-span-2">
                    <p class="text-gray-500">หมายเหตุ:</p>
                    <p class="text-gray-800">{{ $stockDocument->note ?? '-' }}</p>
                </div>
            </div>

            <!-- ตารางรายการสินค้า -->
            <table class="w-full text-left border-collapse mb-8">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-300">
                        <th class="p-3 text-sm font-semibold text-gray-700 w-12 text-center">#</th>
                        <th class="p-3 text-sm font-semibold text-gray-700">รหัสสินค้า (SKU)</th>
                        <th class="p-3 text-sm font-semibold text-gray-700">ชื่อสินค้า</th>
                        <th class="p-3 text-sm font-semibold text-gray-700 text-center">จำนวน</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockDocument->items as $index => $item)
                        <tr class="border-b border-gray-200">
                            <td class="p-3 text-center text-gray-600">{{ $index + 1 }}</td>
                            <td class="p-3 text-gray-600">{{ $item->product->sku }}</td>
                            <td class="p-3 font-medium text-gray-800">{{ $item->product->name }}</td>
                            <td class="p-3 text-center font-bold text-gray-800">{{ $item->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- ลายเซ็น (สำหรับพิมพ์) -->
            <div class="grid grid-cols-2 gap-8 mt-16 text-center text-sm">
                <div>
                    <p class="text-gray-500 mb-16">ลงชื่อ...................................................</p>
                    <p class="text-gray-700 font-medium">({{ $stockDocument->user->name }})</p>
                    <p class="text-gray-500">ผู้จัดทำ / ผู้ส่ง</p>
                </div>
                <div>
                    <p class="text-gray-500 mb-16">ลงชื่อ...................................................</p>
                    <p class="text-gray-700 font-medium">
                        ({{ $stockDocument->receiver_name ?? '................................' }})</p>
                    <p class="text-gray-500">ผู้รับของ</p>
                </div>
            </div>
        </div>
    </div>
@endsection
