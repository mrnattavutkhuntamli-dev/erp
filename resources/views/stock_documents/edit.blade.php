@extends('layouts.app')
@section('title', 'แก้ไขเอกสาร')
@section('content')
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">แก้ไขเอกสาร: {{ $stockDocument->doc_no }}</h1>
            <a href="{{ route('stock-documents.show', $stockDocument->id) }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-sm text-yellow-800">
            <strong>หมายเหตุ:</strong> การแก้ไขนี้จะเปลี่ยนเฉพาะข้อมูลหัวเอกสาร ไม่มีผลต่อจำนวนสต็อกสินค้า (หากต้องการแก้จำนวนสินค้า ให้ยกเลิกเอกสารนี้แล้วสร้างใหม่)
        </div>

        <form action="{{ route('stock-documents.update', $stockDocument->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">วันที่ทำรายการ <span class="text-red-500">*</span></label>
                    <input type="date" name="date" value="{{ old('date', $stockDocument->date) }}" required class="w-full border p-2 rounded">
                </div>

                <!-- แก้ไขส่วนผู้รับ/ผู้ส่ง -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        {{ $stockDocument->type == 'in' ? 'ผู้ส่งของ (Supplier)' : 'ผู้รับของ (ลูกค้า)' }}
                    </label>

                    @if($stockDocument->type == 'in')
                        <!-- ถ้าเป็นใบรับเข้า: เลือกซัพพลายเออร์ -->
                        <select name="supplier_id" class="w-full border p-2 rounded">
                            <option value="">-- เลือกซัพพลายเออร์ --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $stockDocument->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <!-- ถ้าเป็นใบเบิกออก: เลือกลูกค้า -->
                        <select name="customer_id" class="w-full border p-2 rounded">
                            <option value="">-- เลือกลูกค้า --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $stockDocument->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} {{ $customer->license_plate ? '('.$customer->license_plate.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-medium mb-2">หมายเหตุ</label>
                    <textarea name="note" rows="3" class="w-full border p-2 rounded">{{ old('note', $stockDocument->note) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 shadow">บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
@endsection
