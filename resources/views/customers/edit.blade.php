@extends('layouts.app')
@section('title', 'แก้ไขลูกค้า')
@section('content')
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">แก้ไขข้อมูลลูกค้า</h1>
            <a href="{{ route('customers.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded"><ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
        @endif

        <form action="{{ route('customers.update', $customer->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">ชื่อลูกค้า/อู่ซ่อม <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="w-full border p-2 rounded focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full border p-2 rounded focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">อีเมล</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="w-full border p-2 rounded focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-2">ทะเบียนรถ</label>
                    <input type="text" name="license_plate" value="{{ old('license_plate', $customer->license_plate) }}" class="w-full border p-2 rounded focus:ring focus:ring-blue-200">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">ที่อยู่</label>
                <textarea name="address" rows="3" class="w-full border p-2 rounded focus:ring focus:ring-blue-200">{{ old('address', $customer->address) }}</textarea>
            </div>
            <div class="mb-6 flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 rounded">
                <label for="is_active" class="ml-2 text-gray-700">เปิดใช้งาน</label>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded hover:bg-yellow-600 shadow">อัปเดตข้อมูล</button>
            </div>
        </form>
    </div>
@endsection
