@extends('layouts.app')
@section('title', 'สร้างเอกสารใหม่')
@section('content')
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold {{ $type == 'in' ? 'text-green-600' : 'text-red-600' }}">
                {{ $type == 'in' ? '📥 สร้างใบรับสินค้า (Goods Receipt)' : '📤 สร้างใบเบิกสินค้า (Stock Issue)' }}
            </h1>
            <a href="{{ route('stock-documents.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if (session('error'))
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('stock-documents.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <!-- ข้อมูลหัวเอกสาร -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 bg-gray-50 p-4 rounded border">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">วันที่ทำรายการ <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                        class="w-full border p-2 rounded">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">{{ $type == 'in' ? 'ผู้ส่งของ (Supplier)' : 'ผู้รับของ (ลูกค้า/ช่าง)' }}</label>
                        @if ($type == 'in')
                            <!-- ถ้าเป็นรับเข้า ให้เลือกจาก Dropdown ซัพพลายเออร์ -->
                            <select name="supplier_id" class="w-full border p-2 rounded">
                                <option value="">-- เลือกซัพพลายเออร์ --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <!-- ถ้าเป็นเบิกออก ใช้ช่องพิมพ์ชื่อผู้รับ (เพราะยังไม่มีระบบ Customer) -->
                            <input type="text" name="receiver_name" value="{{ old('receiver_name') }}"
                                class="w-full border p-2 rounded" placeholder="ระบุชื่อลูกค้า/ช่าง...">
                        @endif
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">หมายเหตุ</label>
                    <input type="text" name="note" value="{{ old('note') }}" class="w-full border p-2 rounded"
                        placeholder="เช่น เลขที่ PO...">
                </div>
            </div>

            <!-- รายการสินค้า (Dynamic) -->
            <h3 class="text-lg font-bold mb-4 text-gray-800">รายการสินค้า</h3>
            <div id="items-container" class="space-y-3 mb-4">
                <!-- แถวรายการที่ 1 (Default) -->
                <div class="flex items-center space-x-3 item-row">
                    <div class="flex-1">
                        <select name="items[0][product_id]" required class="w-full border p-2 rounded">
                            <option value="">-- เลือกสินค้า --</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }} (คงเหลือ:
                                    {{ $product->stock }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-32">
                        <input type="number" name="items[0][quantity]" required min="1" value="1"
                            class="w-full border p-2 rounded text-center" placeholder="จำนวน">
                    </div>
                    <button type="button"
                        class="remove-item bg-red-100 text-red-600 px-3 py-2 rounded hover:bg-red-200 hidden">ลบ</button>
                </div>
            </div>

            <button type="button" id="add-item"
                class="mb-8 bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm font-medium">+
                เพิ่มรายการสินค้า</button>

            <div class="flex justify-end border-t pt-4">
                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded hover:bg-blue-700 shadow font-bold">
                    บันทึกเอกสาร
                </button>
            </div>
        </form>
    </div>

    <!-- JavaScript สำหรับเพิ่ม/ลบแถว -->
    <script>
        let itemIndex = 1;
        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'flex items-center space-x-3 item-row';
            newRow.innerHTML = `
                <div class="flex-1">
                    <select name="items[${itemIndex}][product_id]" required class="w-full border p-2 rounded">
                        <option value="">-- เลือกสินค้า --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }} (คงเหลือ: {{ $product->stock }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-32">
                    <input type="number" name="items[${itemIndex}][quantity]" required min="1" value="1" class="w-full border p-2 rounded text-center">
                </div>
                <button type="button" class="remove-item bg-red-100 text-red-600 px-3 py-2 rounded hover:bg-red-200">ลบ</button>
            `;
            container.appendChild(newRow);
            itemIndex++;
        });

        document.getElementById('items-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('.item-row').remove();
            }
        });
    </script>
@endsection
