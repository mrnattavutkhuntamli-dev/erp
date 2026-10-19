@extends('layouts.app')
@section('title', 'จัดการลูกค้า')
@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">รายชื่อลูกค้า</h1>
        <a href="{{ route('customers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 shadow">+ เพิ่มลูกค้าใหม่</a>
    </div>

    @if (session('success')) <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">{{ session('success') }}</div> @endif

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 border-b">
                    <th class="p-4">ชื่อลูกค้า / อู่</th>
                    <th class="p-4">เบอร์โทร</th>
                    <th class="p-4">ทะเบียนรถ</th>
                    <th class="p-4">ที่อยู่</th>
                    <th class="p-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-4 font-medium text-gray-800">{{ $customer->name }}</td>
                        <td class="p-4 text-gray-600">{{ $customer->phone ?? '-' }}</td>
                        <td class="p-4">
                            @if($customer->license_plate)
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm font-mono border border-yellow-300">{{ $customer->license_plate }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="p-4 text-gray-600 text-sm">{{ Str::limit($customer->address, 30) ?? '-' }}</td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('customers.edit', $customer->id) }}" class="bg-yellow-400 text-white px-3 py-1 rounded hover:bg-yellow-500 text-sm">แก้ไข</a>
                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบลูกค้ารายนี้?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-gray-500">ยังไม่มีข้อมูลลูกค้า</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $customers->links() }}</div>
    </div>
@endsection
