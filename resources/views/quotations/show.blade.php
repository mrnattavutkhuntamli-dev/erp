@extends('layouts.app')
@section('title', 'รายละเอียดใบเสนอราคา')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6 print:hidden">
            <a href="{{ route('quotations.index') }}" class="text-blue-600 hover:underline">&larr; กลับไปหน้ารายการ</a>
            <div class="flex gap-2">
                <a href="{{ route('quotations.pdf', $quotation->id) }}" class="bg-red-600 text-white px-4 py-2 rounded-lg shadow hover:bg-red-700">
                    📄 ดาวน์โหลด PDF
                </a>
            </div>
        </div>

        @if (session('success')) <div class="bg-emerald-100 text-emerald-700 p-4 mb-4 rounded-lg">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg">{{ session('error') }}</div> @endif

        <div class="bg-white p-10 rounded-xl shadow-md border border-slate-200 print:shadow-none print:border-none print:p-0">
            <div class="flex justify-between items-start border-b-2 border-slate-800 pb-6 mb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">🚗 AutoPart ERP</h1>
                    <p class="text-slate-500 text-sm mt-1">ร้านขายอะไหล่รถยนต์ครบวงจร</p>
                    <p class="text-slate-500 text-sm">123 ถนนสุขุมวิท กรุงเทพฯ | โทร: 02-123-4567</p>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold text-blue-600">ใบเสนอราคา</h2>
                    <p class="font-medium text-slate-800 mt-1">เลขที่: {{ $quotation->quote_no }}</p>
                    <p class="text-slate-600 text-sm">วันที่: {{ $quotation->quote_date->format('d/m/Y') }}</p>
                    <p class="text-slate-600 text-sm">ใช้ได้ถึง: {{ $quotation->valid_until->format('d/m/Y') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                <div>
                    <p class="text-slate-500">ลูกค้า:</p>
                    <p class="font-bold text-slate-800">{{ $quotation->customer->name ?? 'ลูกค้าทั่วไป' }}</p>
                    @if($quotation->customer)
                        <p class="text-slate-600">{{ $quotation->customer->phone }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-slate-500">สถานะ:</p>
                    <p class="font-bold text-slate-800 uppercase">{{ $quotation->status }}</p>
                </div>
            </div>

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
                    @foreach($quotation->items as $index => $item)
                        <tr class="border-b border-slate-200">
                            <td class="p-3 text-center text-slate-600">{{ $index + 1 }}</td>
                            <td class="p-3">
                                <div class="font-medium text-slate-800">{{ $item->product->name }}</div>
                                <div class="text-xs text-slate-500">{{ $item->product->sku }}</div>
                            </td>
                            <td class="p-3 text-center font-bold">{{ $item->quantity }}</td>
                            <td class="p-3 text-right">฿{{ number_format($item->price, 2) }}</td>
                            <td class="p-3 text-right font-bold">฿{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="border-t-2 border-slate-300 pt-4 text-right">
                <p class="text-sm">ยอดรวม: ฿{{ number_format($quotation->subtotal, 2) }}</p>
                <p class="text-sm">ส่วนลด: -฿{{ number_format($quotation->discount, 2) }}</p>
                <p class="text-lg font-bold text-slate-800 mt-2">ยอดสุทธิ: <span class="text-blue-600">฿{{ number_format($quotation->total, 2) }}</span></p>
            </div>

            @if($quotation->status === 'draft' || $quotation->status === 'sent')
                <div class="mt-8 flex justify-end space-x-3 print:hidden border-t pt-4">
                    <form action="{{ route('quotations.update-status', $quotation->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 shadow-lg font-bold" onclick="return confirm('ลูกค้าปฏิเสธใบเสนอราคานี้?')">❌ ปฏิเสธ</button>
                    </form>
                    <form action="{{ route('quotations.convert', $quotation->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 shadow-lg font-bold" onclick="return confirm('แปลงเป็นใบเสร็จและตัดสต็อกทันที?')">✅ แปลงเป็นใบเสร็จ</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
