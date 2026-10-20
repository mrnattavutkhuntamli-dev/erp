@extends('layouts.app')
@section('title', 'ปรับปรุงสต็อก')

@section('content')
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow ring-1 ring-slate-200">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-slate-800">⚖️ ปรับปรุงสต็อก</h1>
            <a href="{{ route('stock-adjustments.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-lg">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('stock-adjustments.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-slate-700 font-medium mb-2">สินค้าที่ต้องการปรับปรุง <span class="text-red-500">*</span></label>
                <select name="product_id" id="product_id" required class="w-full border-slate-300 p-2 rounded-lg">
                    <option value="">-- เลือกสินค้า --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-stock="{{ $product->stock }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->sku }} - {{ $product->name }} (คงเหลือ: {{ $product->stock }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-slate-700 font-medium mb-2">ประเภทการปรับปรุง <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                    <label class="flex items-center cursor-pointer p-3 border rounded-lg flex-1 {{ old('type') === 'addition' ? 'border-green-500 bg-green-50' : 'border-slate-300' }}">
                        <input type="radio" name="type" value="addition" required {{ old('type') === 'addition' ? 'checked' : '' }} class="mr-2 text-green-600">
                        <span class="font-medium text-slate-800">เพิ่มสต็อก (+)</span>
                    </label>
                    <label class="flex items-center cursor-pointer p-3 border rounded-lg flex-1 {{ old('type') === 'subtraction' ? 'border-red-500 bg-red-50' : 'border-slate-300' }}">
                        <input type="radio" name="type" value="subtraction" required {{ old('type') === 'subtraction' ? 'checked' : '' }} class="mr-2 text-red-600">
                        <span class="font-medium text-slate-800">ลดสต็อก (-)</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-slate-700 font-medium mb-2">จำนวน <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" value="{{ old('quantity') }}" required min="1" class="w-full border-slate-300 p-2 rounded-lg" placeholder="ระบุจำนวนที่ต้องการปรับ">
            </div>

            <div class="mb-4">
                <label class="block text-slate-700 font-medium mb-2">เหตุผลการปรับปรุง <span class="text-red-500">*</span></label>
                <select name="reason" required class="w-full border-slate-300 p-2 rounded-lg">
                    <option value="">-- เลือกเหตุผล --</option>
                    <option value="damaged" {{ old('reason') === 'damaged' ? 'selected' : '' }}>ชำรุดเสียหาย</option>
                    <option value="lost" {{ old('reason') === 'lost' ? 'selected' : '' }}>สูญหาย</option>
                    <option value="stocktake" {{ old('reason') === 'stocktake' ? 'selected' : '' }}>นับสต็อกจริงแล้วไม่ตรง</option>
                    <option value="return" {{ old('reason') === 'return' ? 'selected' : '' }}>คืนสินค้าจากร้านค้า</option>
                    <option value="other" {{ old('reason') === 'other' ? 'selected' : '' }}>อื่นๆ</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-slate-700 font-medium mb-2">หมายเหตุ</label>
                <textarea name="note" rows="2" class="w-full border-slate-300 p-2 rounded-lg" placeholder="เช่น รายละเอียดเพิ่มเติม...">{{ old('note') }}</textarea>
            </div>

            <div class="flex justify-end border-t pt-4">
                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 shadow-lg font-bold">บันทึกการปรับปรุง</button>
            </div>
        </form>
    </div>
@endsection
