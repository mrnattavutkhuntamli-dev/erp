@extends('layouts.app')
@section('title', 'จัดการซัพพลายเออร์')
@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">รายการซัพพลายเออร์</h1>
        <a href="{{ route('suppliers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 shadow">+ เพิ่มซัพพลายเออร์</a>
    </div>

    @if (session('success')) <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">{{ session('success') }}</div> @endif

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 border-b">
                    <th class="p-4">ชื่อบริษัท/ร้าน</th>
                    <th class="p-4">ผู้ติดต่อ</th>
                    <th class="p-4">เบอร์โทร</th>
                    <th class="p-4">อีเมล</th>
                    <th class="p-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-4 font-medium text-gray-800">{{ $supplier->name }}</td>
                        <td class="p-4 text-gray-600">{{ $supplier->contact_name ?? '-' }}</td>
                        <td class="p-4 text-gray-600">{{ $supplier->phone ?? '-' }}</td>
                        <td class="p-4 text-gray-600">{{ $supplier->email ?? '-' }}</td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="bg-yellow-400 text-white px-3 py-1 rounded hover:bg-yellow-500 text-sm">แก้ไข</a>
                                <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-gray-500">ยังไม่มีข้อมูลซัพพลายเออร์</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
