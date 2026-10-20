@extends('layouts.app')
@section('title', 'สร้างใบเสนอราคา')

@section('content')
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow ring-1 ring-slate-200">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-slate-800">📝 สร้างใบเสนอราคา</h1>
            <a href="{{ route('quotations.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg"><ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
        @endif

        <form action="{{ route('quotations.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div class="md:col-span-2">
                    <label class="block text-slate-700 font-medium mb-2">ลูกค้า</label>
                    <select name="customer_id" class="w-full border-slate-300 p-2 rounded-lg">
                        <option value="">-- ลูกค้าทั่วไป --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 font-medium mb-2">วันที่ออก <span class="text-red-500">*</span></label>
                    <input type="date" name="quote_date" value="{{ old('quote_date', date('Y-m-d')) }}" required class="w-full border-slate-300 p-2 rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-slate-700 font-medium mb-2">ใช้ได้ถึงวันที่ <span class="text-red-500">*</span></label>
                    <input type="date" name="valid_until" value="{{ old('valid_until', date('Y-m-d', strtotime('+7 days'))) }}" required class="w-full border-slate-300 p-2 rounded-lg">
                </div>
                <div>
                    <label class="block text-slate-700 font-medium mb-2">ส่วนลด (บาท)</label>
                    <input type="number" step="0.01" name="discount" value="{{ old('discount', 0) }}" min="0" class="w-full border-slate-300 p-2 rounded-lg">
                </div>
            </div>

            <h3 class="text-lg font-bold mb-4 text-slate-800">รายการสินค้า</h3>
            <div id="items-container" class="space-y-3 mb-4">
                <div class="flex items-center space-x-3 item-row">
                    <div class="flex-1">
                        <select name="items[0][product_id]" required class="w-full border-slate-300 p-2 rounded-lg product-select">
                            <option value="">-- เลือกสินค้า --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->sku }} - {{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-24">
                        <input type="number" name="items[0][quantity]" required min="1" value="1" class="w-full border-slate-300 p-2 rounded-lg text-center">
                    </div>
                    <div class="w-32">
                        <input type="number" step="0.01" name="items[0][price]" required min="0" value="0" class="w-full border-slate-300 p-2 rounded-lg text-right price-input">
                    </div>
                    <button type="button" class="remove-item bg-red-100 text-red-600 px-3 py-2 rounded-lg hover:bg-red-200 hidden">ลบ</button>
                </div>
            </div>
            <button type="button" id="add-item" class="mb-8 bg-slate-200 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-300 text-sm font-medium">+ เพิ่มรายการสินค้า</button>

            <div class="mb-6">
                <label class="block text-slate-700 font-medium mb-2">หมายเหตุ</label>
                <textarea name="note" rows="2" class="w-full border-slate-300 p-2 rounded-lg" placeholder="เช่น เงื่อนไขการชำระเงิน...">{{ old('note') }}</textarea>
            </div>

            <div class="flex justify-end border-t pt-4">
                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 shadow-lg font-bold">บันทึกใบเสนอราคา</button>
            </div>
        </form>
    </div>

    <script>
        let itemIndex = 1;
        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'flex items-center space-x-3 item-row';
            newRow.innerHTML = `
                <div class="flex-1">
                    <select name="items[${itemIndex}][product_id]" required class="w-full border-slate-300 p-2 rounded-lg product-select">
                        <option value="">-- เลือกสินค้า --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->sku }} - {{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-24">
                    <input type="number" name="items[${itemIndex}][quantity]" required min="1" value="1" class="w-full border-slate-300 p-2 rounded-lg text-center">
                </div>
                <div class="w-32">
                    <input type="number" step="0.01" name="items[${itemIndex}][price]" required min="0" value="0" class="w-full border-slate-300 p-2 rounded-lg text-right price-input">
                </div>
                <button type="button" class="remove-item bg-red-100 text-red-600 px-3 py-2 rounded-lg hover:bg-red-200">ลบ</button>
            `;
            container.appendChild(newRow);
            itemIndex++;
        });

        document.getElementById('items-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('.item-row').remove();
            }
        });

        // ดึงราคาขายอัตโนมัติเมื่อเลือกสินค้า
        document.getElementById('items-container').addEventListener('change', function(e) {
            if (e.target.classList.contains('product-select')) {
                const selectedOption = e.target.options[e.target.selectedIndex];
                const price = selectedOption.dataset.price || 0;
                const row = e.target.closest('.item-row');
                row.querySelector('.price-input').value = price;
            }
        });
    </script>
@endsection
