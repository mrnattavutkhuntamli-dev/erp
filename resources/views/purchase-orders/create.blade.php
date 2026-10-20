@extends('layouts.app')
@section('title', 'สร้างใบสั่งซื้อ')

@section('content')
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow ring-1 ring-slate-200">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-slate-800">📥 สร้างใบสั่งซื้อ (Purchase Order)</h1>
            <a href="{{ route('purchase-orders.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('purchase-orders.store') }}" method="POST">
            @csrf

            <!-- ข้อมูลหัวเอกสาร -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div>
                    <label class="block text-slate-700 font-medium mb-2">ซัพพลายเออร์ <span class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplier-select" required class="w-full border-slate-300 p-2 rounded-lg">
                        <option value="">-- เลือกซัพพลายเออร์ --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 font-medium mb-2">วันที่สั่ง <span class="text-red-500">*</span></label>
                    <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required class="w-full border-slate-300 p-2 rounded-lg">
                </div>
                <div>
                    <label class="block text-slate-700 font-medium mb-2">คาดว่าจะได้รับ</label>
                    <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="w-full border-slate-300 p-2 rounded-lg">
                </div>
            </div>

            <!-- รายการสินค้า -->
            <h3 class="text-lg font-bold mb-4 text-slate-800">รายการสินค้าที่สั่ง</h3>
            <div id="items-container" class="space-y-3 mb-4">
                <!-- แถวรายการที่ 1 -->
                <div class="flex items-center space-x-3 item-row">
                    <div class="flex-1">
                        <select name="items[0][product_id]" required class="w-full border-slate-300 p-2 rounded-lg product-select">
                            <option value="">-- เลือกสินค้า --</option>
                        </select>
                    </div>
                    <div class="w-24">
                        <input type="number" name="items[0][quantity]" required min="1" value="1" class="w-full border-slate-300 p-2 rounded-lg text-center" placeholder="จำนวน">
                    </div>
                    <div class="w-32">
                        <input type="number" step="0.01" name="items[0][unit_cost]" required min="0" value="0" class="w-full border-slate-300 p-2 rounded-lg text-right" placeholder="ราคา/หน่วย">
                    </div>
                    <button type="button" class="remove-item bg-red-100 text-red-600 px-3 py-2 rounded-lg hover:bg-red-200 hidden">ลบ</button>
                </div>
            </div>

            <button type="button" id="add-item" class="mb-8 bg-slate-200 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-300 text-sm font-medium">+ เพิ่มรายการสินค้า</button>

            <!-- หมายเหตุ -->
            <div class="mb-6">
                <label class="block text-slate-700 font-medium mb-2">หมายเหตุ</label>
                <textarea name="note" rows="2" class="w-full border-slate-300 p-2 rounded-lg" placeholder="เช่น เงื่อนไขการชำระเงิน, รายละเอียดเพิ่มเติม...">{{ old('note') }}</textarea>
            </div>

            <div class="flex justify-end border-t pt-4">
                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 shadow-lg font-bold">บันทึกใบสั่งซื้อ</button>
            </div>
        </form>
    </div>

    <!-- JavaScript สำหรับกรองสินค้าตามซัพพลายเออร์ + แจ้งเตือน -->
    <script>
        // ดึงข้อมูลสินค้าทั้งหมดจาก Laravel ไปให้ JS
        const allProducts = @json($products);

        // ฟังก์ชันอัปเดต Dropdown สินค้าตามซัพพลายเออร์
        function updateProductOptions(selectElement, supplierId) {
            selectElement.innerHTML = '<option value="">-- เลือกสินค้า --</option>';

            if (!supplierId) return;

            const filteredProducts = allProducts.filter(p => p.supplier_id == supplierId);

            if (filteredProducts.length === 0) {
                selectElement.innerHTML = '<option value="">-- ไม่มีสินค้าของซัพพลายเออร์รายนี้ --</option>';
                return;
            }

            filteredProducts.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = `${product.sku} - ${product.name} (ราคาทุน: ฿${product.cost})`;
                selectElement.appendChild(option);
            });
        }

        // เมื่อเลือกซัพพลายเออร์
        document.getElementById('supplier-select').addEventListener('change', function() {
            const supplierId = this.value;

            // ✅ 1. เช็กว่ามีสินค้าถูกเลือกไว้ในแถวอื่นหรือยัง
            let hasSelectedProducts = false;
            document.querySelectorAll('.product-select').forEach(select => {
                if (select.value !== '') hasSelectedProducts = true;
            });

            // ✅ 2. ถ้ามีการเลือกสินค้าไว้แล้ว ให้ถามยืนยันก่อน
            if (hasSelectedProducts) {
                if (!confirm('การเปลี่ยนซัพพลายเออร์จะล้างรายการสินค้าที่เลือกไว้ทั้งหมด คุณต้องการดำเนินการต่อหรือไม่?')) {
                    return; // ถ้ากด Cancel ก็หยุดทำ
                }
            }

            // ✅ 3. อัปเดต Dropdown สินค้าทุกแถว
            document.querySelectorAll('.product-select').forEach(select => {
                updateProductOptions(select, supplierId);
            });
        });

        // --- ระบบเพิ่ม/ลบแถวสินค้า ---
        let itemIndex = 1;
        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'flex items-center space-x-3 item-row';
            newRow.innerHTML = `
                <div class="flex-1">
                    <select name="items[${itemIndex}][product_id]" required class="w-full border-slate-300 p-2 rounded-lg product-select">
                        <option value="">-- เลือกสินค้า --</option>
                    </select>
                </div>
                <div class="w-24">
                    <input type="number" name="items[${itemIndex}][quantity]" required min="1" value="1" class="w-full border-slate-300 p-2 rounded-lg text-center">
                </div>
                <div class="w-32">
                    <input type="number" step="0.01" name="items[${itemIndex}][unit_cost]" required min="0" value="0" class="w-full border-slate-300 p-2 rounded-lg text-right">
                </div>
                <button type="button" class="remove-item bg-red-100 text-red-600 px-3 py-2 rounded-lg hover:bg-red-200">ลบ</button>
            `;
            container.appendChild(newRow);

            const supplierId = document.getElementById('supplier-select').value;
            const newSelect = newRow.querySelector('.product-select');
            updateProductOptions(newSelect, supplierId);

            itemIndex++;
        });

        // ลบแถว
        document.getElementById('items-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('.item-row').remove();
            }
        });

        // ดึงราคาทุนอัตโนมัติเมื่อเลือกสินค้า
        document.getElementById('items-container').addEventListener('change', function(e) {
            if (e.target.classList.contains('product-select')) {
                const productId = e.target.value;
                const product = allProducts.find(p => p.id == productId);
                if (product) {
                    const row = e.target.closest('.item-row');
                    row.querySelector('input[name*="[unit_cost]"]').value = product.cost;
                }
            }
        });
    </script>
@endsection
