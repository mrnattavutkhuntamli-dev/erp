@extends('layouts.app')
@section('title', 'รายละเอียดใบสั่งซื้อ')

@section('content')
    <div class="max-w-4xl mx-auto">
        <!-- ปุ่มควบคุม -->
        <div class="flex justify-between items-center mb-6 print:hidden">
            <a href="{{ route('purchase-orders.index') }}" class="text-blue-600 hover:underline">&larr; กลับไปหน้ารายการ</a>
            <div class="flex gap-2">
                {{-- ✅ ปุ่มลบ (แสดงเฉพาะ Pending) --}}
                @if($purchaseOrder->status === 'pending')
                    <form action="{{ route('purchase-orders.destroy', $purchaseOrder->id) }}" method="POST"
                          onsubmit="return confirm('ยืนยันการลบ PO? (แนะนำให้ใช้การยกเลิกแทน)');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg shadow hover:bg-red-600">
                            🗑️ ลบ PO
                        </button>
                    </form>
                @endif
                <button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded-lg shadow hover:bg-slate-900">
                    🖨️ พิมพ์เอกสาร
                </button>
            </div>
        </div>

        <!-- เอกสาร PO -->
        <div class="bg-white p-10 rounded-xl shadow-md border border-slate-200 print:shadow-none print:border-none print:p-0">
            <!-- Header (ใช้ข้อมูลจาก Settings) -->
            <div class="flex justify-between items-start border-b-2 border-slate-800 pb-6 mb-6">
                <div class="flex items-center gap-4">
                    @if(\App\Models\Setting::get('company_logo'))
                        <img src="{{ asset('storage/logo/' . \App\Models\Setting::get('company_logo')) }}"
                             alt="Logo" class="h-16 w-auto object-contain">
                    @endif
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800">
                            {{ \App\Models\Setting::get('company_name', 'AutoPart ERP') }}
                        </h1>
                        <p class="text-slate-500 text-sm mt-1">{{ \App\Models\Setting::get('company_address') }}</p>
                        <p class="text-slate-500 text-sm">โทร: {{ \App\Models\Setting::get('company_phone') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold text-blue-600">ใบสั่งซื้อ (PO)</h2>
                    <p class="font-medium text-slate-800 mt-1">เลขที่: {{ $purchaseOrder->po_no }}</p>
                    <p class="text-slate-600 text-sm">วันที่สั่ง: {{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d/m/Y') }}</p>
                    <p class="text-slate-600 text-sm">คาดว่าจะได้รับ: {{ $purchaseOrder->expected_date ? \Carbon\Carbon::parse($purchaseOrder->expected_date)->format('d/m/Y') : '-' }}</p>
                </div>
            </div>

            <!-- ข้อมูลทั่วไป -->
            <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                <div>
                    <p class="text-slate-500">ซัพพลายเออร์:</p>
                    <p class="font-bold text-slate-800">{{ $purchaseOrder->supplier->name }}</p>
                    <p class="text-slate-600">{{ $purchaseOrder->supplier->phone }}</p>
                </div>
                <div>
                    <p class="text-slate-500">ผู้สั่งซื้อ:</p>
                    <p class="font-bold text-slate-800">{{ $purchaseOrder->user->name }}</p>
                    <p class="text-slate-600">สถานะ:
                        @if($purchaseOrder->status === 'pending')
                            <span class="text-yellow-600 font-bold">รอรับของ</span>
                        @elseif($purchaseOrder->status === 'received')
                            <span class="text-green-600 font-bold">รับของแล้ว</span>
                        @else
                            <span class="text-red-600 font-bold">ยกเลิก</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- ตารางรายการสินค้า -->
            <table class="w-full text-left border-collapse mb-6">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-300">
                        <th class="p-3 text-sm font-semibold text-slate-700 w-12 text-center">#</th>
                        <th class="p-3 text-sm font-semibold text-slate-700">สินค้า</th>
                        <th class="p-3 text-sm font-semibold text-slate-700 text-center">จำนวน</th>
                        <th class="p-3 text-sm font-semibold text-slate-700 text-right">ราคา/หน่วย</th>
                        <th class="p-3 text-sm font-semibold text-slate-700 text-right">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $index => $item)
                        <tr class="border-b border-slate-200">
                            <td class="p-3 text-center text-slate-600">{{ $index + 1 }}</td>
                            <td class="p-3">
                                <div class="font-medium text-slate-800">{{ $item->product->name }}</div>
                                <div class="text-xs text-slate-500">{{ $item->product->sku }}</div>
                            </td>
                            <td class="p-3 text-center font-bold">{{ $item->quantity }}</td>
                            <td class="p-3 text-right">฿{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="p-3 text-right font-bold">฿{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- ยอดรวม -->
            <div class="text-right border-t-2 border-slate-300 pt-4">
                <p class="text-lg font-bold text-slate-800">ยอดรวมทั้งสิ้น: <span class="text-blue-600">฿{{ number_format($purchaseOrder->total_amount, 2) }}</span></p>
            </div>

            <!-- หมายเหตุ -->
            @if($purchaseOrder->note)
                <div class="mt-6 text-sm">
                    <p class="text-slate-500">หมายเหตุ:</p>
                    <p class="text-slate-800">{{ $purchaseOrder->note }}</p>
                </div>
            @endif

            <!-- ปุ่มรับของ / ยกเลิก (เฉพาะ Pending) -->
            @if($purchaseOrder->status === 'pending')
                <div class="mt-8 flex justify-end space-x-3 print:hidden">
                    <form action="{{ route('purchase-orders.cancel', $purchaseOrder->id) }}" method="POST" onsubmit="return confirm('ยืนยันการยกเลิก PO?');">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 shadow-lg font-bold">❌ ยกเลิก PO</button>
                    </form>
                    <form action="{{ route('purchase-orders.receive', $purchaseOrder->id) }}" method="POST" onsubmit="return confirm('ยืนยันการรับของ? ระบบจะเพิ่มสต็อกสินค้าให้อัตโนมัติ');">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 shadow-lg font-bold">✅ รับของ (เพิ่มสต็อก)</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
