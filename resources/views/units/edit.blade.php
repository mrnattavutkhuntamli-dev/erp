@extends('layouts.app')
@section('title', 'แก้ไขหน่วยนับ')
@section('content')
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">แก้ไขหน่วยนับ</h1>
            <a href="{{ route('units.index') }}" class="text-blue-600 hover:underline">&larr; กลับ</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('units.update', $unit->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">ชื่อหน่วยนับ <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $unit->name) }}" required class="w-full border p-2 rounded focus:ring focus:ring-blue-200">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">ชื่อย่อ <span class="text-red-500">*</span></label>
                <input type="text" name="short_name" value="{{ old('short_name', $unit->short_name) }}" required class="w-full border p-2 rounded focus:ring focus:ring-blue-200">
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded hover:bg-yellow-600 shadow">อัปเดต</button>
            </div>
        </form>
    </div>
@endsection
