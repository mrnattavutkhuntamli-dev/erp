@extends('layouts.app')
@section('title', 'รายละเอียดใบรับเงิน')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6 print:hidden">
            <a href="{{ route('receipts.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if (session('success')) <div class="bg-emerald-100 text-emerald-700 p-4 mb-4 rounded-lg print:hidden">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg print:hidden">{{ session('error') }}</div> @endif

        {{-- ============================================ --}}
        {{-- กรณีที่ 1: แสดงฟอร์มรับชำระเงินใหม่ (มี $sale) --}}
        {{-- ============================================ --}}
        @if(isset($sale) && $sale)
            <div class="bg-white p-8 rounded-xl shadow ring-1 ring-slate-200 border border-slate-200">
                <h1 class="text-2xl font-bold text-slate-800 mb-6">💵 บันทึกรับชำระเงิน</h1>

                <div class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-200">
                    <p class="text-sm text-slate-600">บิลที่เลือก: <span class="font-bold text-blue-600">{{ $sale->invoice_no }}</span></p>
                    <p class="text-sm text-slate-600">ลูกค้า: <span class="font-bold">{{ $sale->customer->name ?? 'ลูกค้าทั่วไป' }}</span></p>
                    <p class="text-sm text-slate-600">ยอดรวม: <span class="font-bold">฿{{ number_format($sale->total, 2) }}</span></p>
                    <p class="text-sm text-slate-600">จ่ายแล้ว: <span class="font-bold">฿{{ number_format($sale->paid_amount, 2) }}</span></p>
                    <p class="text-sm text-red-600">ค้างชำระ: <span class="font-bold">฿{{ number_format($sale->total - $sale->paid_amount, 2) }}</span></p>
                </div>

                <form action="{{ route('receipts.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                    <div class="mb-4">
                        <label class="block text-slate-700 font-medium mb-2">วันที่รับชำระ <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="w-full border-slate-300 p-2 rounded-lg">
                    </div>

                    <div class="mb-4">
                        <label class="block text-slate-700 font-medium mb-2">จำนวนเงินที่รับ <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="amount" value="{{ $sale->total - $sale->paid_amount }}" required min="0.01" max="{{ $sale->total - $sale->paid_amount }}" class="w-full border-slate-300 p-2 rounded-lg text-right text-lg font-bold">
                    </div>

                    <div class="mb-4">
                        <label class="block text-slate-700 font-medium mb-2">วิธีชำระ <span class="text-red-500">*</span></label>
                        <select name="payment_method" required class="w-full border-slate-300 p-2 rounded-lg">
                            <option value="cash">เงินสด</option>
                            <option value="transfer">โอนเงิน</option>
                            <option value="credit_card">บัตรเครดิต</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-slate-700 font-medium mb-2">หมายเหตุ</label>
                        <textarea name="note" rows="2" class="w-full border-slate-300 p-2 rounded-lg"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 shadow-lg font-bold">✅ บันทึกการรับเงิน</button>
                    </div>
                </form>
            </div>

        {{-- ============================================ --}}
        {{-- กรณีที่ 2: แสดงรายละเอียด Receipt ที่มีอยู่แล้ว --}}
        {{-- ============================================ --}}
        @elseif(isset($receipt) && $receipt->exists)
            <div class="bg-white p-8 rounded-xl shadow ring-1 ring-slate-200 border border-slate-200 print:shadow-none print:border-none">
                <div class="text-center border-b border-dashed border-gray-400 pb-4 mb-6">
                    <h1 class="text-2xl font-bold text-slate-800">🧾 ใบรับชำระเงิน</h1>
                    <p class="text-sm text-slate-500">AutoPart ERP | โทร: 02-123-4567</p>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                    <div>
                        <p class="text-slate-500">เลขที่ใบรับเงิน:</p>
                        <p class="font-bold text-slate-800">{{ $receipt->receipt_no }}</p>
                        <p class="text-slate-500 mt-2">วันที่รับชำระ:</p>
                        <p class="font-medium text-slate-800">{{ $receipt->payment_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-slate-500">อ้างอิงบิล:</p>
                        <p class="font-bold text-blue-600">{{ $receipt->sale->invoice_no }}</p>
                        <p class="text-slate-500 mt-2">ลูกค้า:</p>
                        <p class="font-medium text-slate-800">{{ $receipt->sale->customer->name ?? 'ลูกค้าทั่วไป' }}</p>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-lg mb-6">
                    <p class="text-slate-500 text-sm">ยอดที่รับชำระ:</p>
                    <p class="text-3xl font-bold text-emerald-600">฿{{ number_format($receipt->amount, 2) }}</p>
                    <p class="text-sm text-slate-600 mt-2">วิธีชำระ:
                        @if($receipt->payment_method === 'cash') เงินสด
                        @elseif($receipt->payment_method === 'transfer') โอนเงิน
                        @else บัตรเครดิต @endif
                    </p>
                    <p class="text-sm text-slate-600">พนักงานผู้รับเงิน: {{ $receipt->user->name }}</p>
                </div>

                @if($receipt->note)
                    <div class="text-sm mb-4">
                        <p class="text-slate-500">หมายเหตุ:</p>
                        <p class="text-slate-800">{{ $receipt->note }}</p>
                    </div>
                @endif

                @if($receipt->status === 'voided')
                    <div class="bg-red-50 border border-red-200 p-4 rounded-lg mt-4 print:hidden">
                        <p class="text-red-700 font-bold">⚠️ ใบรับเงินนี้ถูกยกเลิกแล้ว</p>
                        <p class="text-sm text-red-600">เหตุผล: {{ $receipt->void_reason }}</p>
                        <p class="text-sm text-red-600">ยกเลิกโดย: {{ $receipt->voidedBy->name ?? '-' }} เมื่อ {{ $receipt->voided_at->format('d/m/Y H:i') }}</p>
                    </div>
                @else
                    <div class="mt-8 flex justify-between print:hidden border-t pt-4">
                        <button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded shadow hover:bg-slate-900">🖨️ พิมพ์ใบรับเงิน</button>

                        <button type="button" onclick="openVoidModal()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            ❌ ยกเลิกใบรับเงิน
                        </button>
                    </div>

                    <!-- Modal สำหรับกรอกเหตุผลการ Void -->
                    <div id="voidModal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center">
                        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-md w-full">
                            <h3 class="text-lg font-bold text-slate-800 mb-4">⚠️ ยกเลิกใบรับเงิน</h3>
                            <p class="text-sm text-slate-600 mb-4">กรุณาระบุเหตุผลการยกเลิก (จำเป็น)</p>
                            <form action="{{ route('receipts.destroy', $receipt->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <textarea name="void_reason" required rows="3" placeholder="เช่น ลูกค้าโอนผิดบัญชี..." class="w-full border p-2 rounded-lg mb-4"></textarea>
                                <div class="flex justify-end space-x-2">
                                    <button type="button" onclick="closeVoidModal()" class="bg-slate-200 px-4 py-2 rounded-lg hover:bg-slate-300">ยกเลิก</button>
                                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">ยืนยันการยกเลิก</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        function openVoidModal() {
                            document.getElementById('voidModal').classList.remove('hidden');
                            document.getElementById('voidModal').classList.add('flex');
                        }
                        function closeVoidModal() {
                            document.getElementById('voidModal').classList.add('hidden');
                            document.getElementById('voidModal').classList.remove('flex');
                        }
                    </script>
                @endif
            </div>

        {{-- ============================================ --}}
        {{-- กรณีที่ 3: ไม่พบข้อมูล --}}
        {{-- ============================================ --}}
        @else
            <div class="bg-red-100 text-red-700 p-4 rounded-lg">ไม่พบข้อมูลใบรับเงิน หรือบิลที่ระบุ</div>
        @endif
    </div>
@endsection
