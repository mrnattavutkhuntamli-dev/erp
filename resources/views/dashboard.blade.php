@extends('layouts.app')

@section('title', 'ภาพรวมระบบ (Dashboard)')

@section('content')
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Card สินค้า -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-sm font-medium mb-1">สินค้าทั้งหมด</h3>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($totalProducts) }} <span class="text-base font-normal text-gray-500">รายการ</span></p>
        </div>

        <!-- Card มูลค่าสต็อก -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <h3 class="text-gray-500 text-sm font-medium mb-1">มูลค่าสต็อกรวม (ราคาขาย)</h3>
            <p class="text-3xl font-bold text-gray-800">฿{{ number_format($totalStockValue, 2) }}</p>
        </div>

        <!-- Card ซัพพลายเออร์ -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <h3 class="text-gray-500 text-sm font-medium mb-1">ซัพพลายเออร์</h3>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($totalSuppliers) }} <span class="text-base font-normal text-gray-500">ราย</span></p>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b bg-red-50">
            <h3 class="text-lg font-semibold text-red-700 flex items-center">
                ⚠️ สินค้าที่สต็อกเหลือน้อย (ต่ำกว่า 10 ชิ้น)
            </h3>
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-sm">
                    <th class="p-4 border-b">SKU</th>
                    <th class="p-4 border-b">ชื่อสินค้า</th>
                    <th class="p-4 border-b">สต็อกคงเหลือ</th>
                    <th class="p-4 border-b text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lowStockProducts as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="p-4 border-b">{{ $product->sku }}</td>
                        <td class="p-4 border-b font-medium">{{ $product->name }}</td>
                        <td class="p-4 border-b text-red-600 font-bold">{{ $product->stock }} ชิ้น</td>
                        <td class="p-4 border-b text-right">
                            <a href="{{ route('products.edit', $product->id) }}" class="text-blue-600 hover:underline text-sm">เติมสต็อก</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-500">สต็อกสินค้าทุกตัวอยู่ในระดับปกติ 🎉</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
