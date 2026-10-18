<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสินค้า - Mini ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">แก้ไขสินค้า: {{ $product->name }}</h1>
            <a href="{{ route('products.index') }}" class="text-blue-600 hover:underline">&larr; กลับไปหน้ารายการ</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT') <!-- สำคัญมาก! ต้องมีเพื่อบอก Laravel ว่านี่คือการอัปเดต -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">ชื่อสินค้า <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">รหัสสินค้า (SKU) <span class="text-red-500">*</span></label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">หมวดหมู่</label>
                    <select name="category_id" class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">ซัพพลายเออร์</label>
                    <select name="supplier_id" class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
                        <option value="">-- เลือกซัพพลายเออร์ --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">หน่วยนับ</label>
                    <select name="unit_id" class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
                        <option value="">-- เลือกหน่วยนับ --</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">ราคาต้นทุน (บาท) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="cost" value="{{ old('cost', $product->cost) }}" required class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">ราคาขาย (บาท) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">จำนวนสต็อก <span class="text-red-500">*</span></label>
                    <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" required class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">รายละเอียดสินค้า</label>
                <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm p-2 border">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="mb-6 flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-gray-700">เปิดใช้งาน (แสดงสินค้า)</label>
            </div>

            <!-- ส่วนจัดการรูปภาพเดิม -->
            <div class="mb-6 border-t pt-4">
                <label class="block text-gray-700 font-medium mb-3 text-lg">รูปภาพปัจจุบัน</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    @forelse($product->images as $image)
                        <div class="relative bg-gray-100 p-2 rounded border {{ $image->is_primary ? 'border-blue-500 ring-2 ring-blue-200' : '' }}">
                            <img src="{{ asset('storage/' . $image->image_path) }}" class="w-full h-32 object-cover rounded mb-2">

                            <div class="flex flex-col space-y-1 text-sm">
                                <!-- Checkbox สำหรับเลือกตั้งเป็นรูปหน้าปก -->
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="primary_image_id" value="{{ $image->id }}" {{ $image->is_primary ? 'checked' : '' }} class="mr-2">
                                    ตั้งเป็นหน้าปก
                                </label>

                                <!-- Checkbox สำหรับเลือกลบรูปนี้ -->
                                <label class="flex items-center text-red-600 cursor-pointer">
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="mr-2">
                                    ลบรูปนี้
                                </label>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 col-span-4">ยังไม่มีรูปภาพสำหรับสินค้านี้</p>
                    @endforelse
                </div>
            </div>

            <!-- ส่วนอัปโหลดรูปใหม่ -->
            <div class="mb-6 border-t pt-4">
                <label class="block text-gray-700 font-medium mb-2">อัปโหลดรูปภาพใหม่เพิ่มเติม</label>
                <input type="file" name="images[]" multiple accept="image/*" class="w-full text-gray-700 border p-2 rounded">
                <p class="text-sm text-gray-500 mt-1">* รูปใหม่ที่อัปโหลดจะถูกเพิ่มต่อจากรูปเดิม (ไม่ทับรูปเก่า)</p>
            </div>

            <div class="flex justify-end mt-8">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-md hover:bg-yellow-600 font-medium shadow">
                    อัปเดตข้อมูลสินค้า
                </button>
            </div>
        </form>
    </div>
</body>
</html>
