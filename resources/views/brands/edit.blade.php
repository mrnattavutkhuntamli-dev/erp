@extends('layouts.app')
@section('title', 'แก้ไขยี่ห้ออะไหล่')
@section('content')
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">แก้ไขยี่ห้ออะไหล่</h1>
            <a href="{{ route('brands.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>
        <form action="{{ route('brands.update', $brand->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">ชื่อยี่ห้อ <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $brand->name) }}" required class="w-full border p-2 rounded focus:ring focus:ring-blue-200">
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded hover:bg-yellow-600 shadow">อัปเดต</button>
            </div>
        </form>
    </div>
@endsection
