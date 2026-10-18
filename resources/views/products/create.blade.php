<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสินค้าใหม่ - Mini ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">เพิ่มสินค้าใหม่</h1>
            <a href="{{ route('products.index') }}" class="text-blue-600 hover:underline">&larr; กลับไปหน้ารายการ</a>
        </div>

        <!-- แสดง Error ทั้งหมด (ถ้ามี) -->
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ฟอร์มต้องมี enctype="multipart/form-data" เพื่อให้อัปโหลดไฟล์ได้ -->
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- ชื่อสินค้า -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">ชื่อสินค้า <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-2 border">
                </div>

                <!-- SKU -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">รหัสสินค้า (SKU) <span class="text-red-500">*</span></label>
                    <input type="text" name="sku" value="กำลังสร้างอัตโนมัติ..." readonly class="w-full bg-gray-100 border-gray-300 rounded-md shadow-sm p-2 border text-gray-500 cursor-not-allowed">
                </div>

                <!-- หมวดหมู่ -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">หมวดหมู่</label>
                    <select name="category_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-2 border">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- ซัพพลายเออร์ -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">ซัพพลายเออร์</label>
                    <select name="supplier_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-2 border">
                        <option value="">-- เลือกซัพพลายเออร์ --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- หน่วยนับ -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">หน่วยนับ</label>
                    <select name="unit_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-2 border">
                        <option value="">-- เลือกหน่วยนับ --</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- ราคาต้นทุน -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">ราคาต้นทุน (บาท) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="cost" value="{{ old('cost', 0) }}" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-2 border">
                </div>

                <!-- ราคาขาย -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">ราคาขาย (บาท) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="price" value="{{ old('price') }}" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-2 border">
                </div>

                <!-- สต็อก -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">จำนวนสต็อก <span class="text-red-500">*</span></label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-2 border">
                </div>
            </div>

            <!-- รายละเอียด -->
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">รายละเอียดสินค้า</label>
                <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-2 border">{{ old('description') }}</textarea>
            </div>

            <!-- อัปโหลดรูปภาพ -->
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">รูปภาพสินค้า (เลือกได้หลายรูป รูปแรกจะเป็นหน้าปก)</label>
                <input type="file" name="images[]" multiple accept="image/*" class="w-full text-gray-700 border p-2 rounded">
            </div>

            <!-- สถานะการแสดง -->
            <div class="mb-6 flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="is_active" class="ml-2 block text-gray-700">เปิดใช้งาน (แสดงสินค้า)</label>
            </div>

            <!-- ปุ่มบันทึก -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-medium shadow">
                    บันทึกสินค้า
                </button>
            </div>
        </form>
    </div>
</body>
</html>
