@extends('layouts.app')
@section('title', 'เพิ่มยี่ห้อรถ')
@section('content')
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">เพิ่มยี่ห้อรถใหม่</h1>
            <a href="{{ route('car-brands.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>
        <form action="{{ route('car-brands.store') }}" method="POST">
            @csrf
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">ชื่อยี่ห้อรถ <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full border p-2 rounded focus:ring focus:ring-blue-200" placeholder="เช่น Toyota, Isuzu, Honda">
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 shadow">บันทึก</button>
            </div>
        </form>
    </div>
@endsection
