@extends('layouts.app')
@section('title', 'จัดการหมวดหมู่')
@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">รายการหมวดหมู่</h1>
        <a href="{{ route('categories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 shadow">+ เพิ่มหมวดหมู่</a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-700 p-4 mb-4 rounded border border-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 text-red-700 p-4 mb-4 rounded border border-red-200">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 border-b">
                    <th class="p-4">ชื่อหมวดหมู่</th>
                    <th class="p-4">Slug</th>
                    <th class="p-4">รายละเอียด</th>
                    <th class="p-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-4 font-medium text-gray-800">{{ $category->name }}</td>
                        <td class="p-4 text-gray-500">{{ $category->slug }}</td>
                        <td class="p-4 text-gray-600">{{ $category->description ?? '-' }}</td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('categories.edit', $category->id) }}" class="bg-yellow-400 text-white px-3 py-1 rounded hover:bg-yellow-500 text-sm">แก้ไข</a>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center text-gray-500">ยังไม่มีข้อมูลหมวดหมู่</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
