@extends('layouts.app')

@section('title', 'จัดการสินค้า')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">รายการสินค้าทั้งหมด</h1>
        <a href="{{ route('products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 shadow">
            + เพิ่มสินค้า
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-700 p-4 mb-4 rounded border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 border-b">
                    <th class="p-4">รูป</th>
                    <th class="p-4">SKU</th>
                    <th class="p-4">ชื่อสินค้า</th>
                    <th class="p-4">หมวดหมู่</th>
                    <th class="p-4">ราคาต้นทุ่น</th>
                    <th class="p-4">ราคาขาย</th>
                    <th class="p-4">สต็อก</th>
                    <th class="p-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50 border-b">
                        <td class="p-4">
                            @if ($product->primary_image_path)
                                <img src="{{ asset('storage/' . $product->primary_image_path) }}" alt="รูปสินค้า"
                                    class="w-16 h-16 object-cover rounded shadow-sm">
                            @else
                                <div class="w-16 h-16 bg-gray-200 flex items-center justify-center rounded text-xs text-gray-500">
                                    ไม่มีรูป
                                </div>
                            @endif
                        </td>
                        <td class="p-4 text-gray-600">{{ $product->sku }}</td>
                        <td class="p-4 font-medium text-gray-800">{{ $product->name }}</td>
                        <td class="p-4 text-gray-600">{{ $product->category_name ?? '-' }}</td>
                        <td class="p-4 text-gray-800">
                            <span>
                              ฿{{ number_format($product->cost, 2) }}
                            </span>
                              {{ $product->unit_name ?? '' }}
                        </td>
                        <td class="p-4 text-gray-800">
                            <span> ฿{{ number_format($product->price, 2) }}</span>
                         {{ $product->unit_name ?? '' }}
                        </td>
                        <td class="p-4">
                            <span class="{{ $product->stock < 10 ? 'text-red-600 font-bold' : 'text-gray-800' }}">
                                {{ $product->stock }}
                            </span>
                            {{ $product->unit_name ?? '' }}
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('products.edit', $product->id) }}"
                                    class="bg-yellow-400 text-white px-3 py-1 rounded hover:bg-yellow-500 text-sm">
                                    แก้ไข
                                </a>

                                <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                    onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบสินค้านี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
                                        ลบ
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-500">
                            ยังไม่มีข้อมูลสินค้าในระบบ
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">
        {{ $products->withQueryString()->links() }}
    </div>
@endsection
