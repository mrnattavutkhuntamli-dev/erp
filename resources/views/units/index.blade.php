@extends('layouts.app')
@section('title', 'จัดการหน่วยนับ')
@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">รายการหน่วยนับ</h1>
        <a href="{{ route('units.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 shadow">+ เพิ่มหน่วยนับ</a>
    </div>

    @if (session('success')) <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">{{ session('error') }}</div> @endif

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 border-b">
                    <th class="p-4">ชื่อหน่วยนับ</th>
                    <th class="p-4">ชื่อย่อ</th>
                    <th class="p-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-4 font-medium text-gray-800">{{ $unit->name }}</td>
                        <td class="p-4 text-gray-500">{{ $unit->short_name }}</td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('units.edit', $unit->id) }}" class="bg-yellow-400 text-white px-3 py-1 rounded hover:bg-yellow-500 text-sm">แก้ไข</a>
                                <form action="{{ route('units.destroy', $unit->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="p-8 text-center text-gray-500">ยังไม่มีข้อมูลหน่วยนับ</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
