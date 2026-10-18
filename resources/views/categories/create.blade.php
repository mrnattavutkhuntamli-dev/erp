@extends('layouts.app')
@section('title', 'เพิ่มหมวดหมู่')
@section('content')
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">เพิ่มหมวดหมู่ใหม่</h1>
            <a href="{{ route('categories.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('categories.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">ชื่อหมวดหมู่ <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full border p-2 rounded focus:ring focus:ring-blue-200">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">รายละเอียด</label>
                <textarea name="description" rows="3" class="w-full border p-2 rounded focus:ring focus:ring-blue-200">{{ old('description') }}</textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 shadow">บันทึก</button>
            </div>
        </form>
    </div>
@endsection
