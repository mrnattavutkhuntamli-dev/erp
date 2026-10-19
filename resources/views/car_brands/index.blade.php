@extends('layouts.app')
@section('title', 'ยี่ห้อและรุ่นรถ')
@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">ยี่ห้อและรุ่นรถ</h1>
        <a href="{{ route('car-brands.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 shadow">+ เพิ่มยี่ห้อรถ</a>
    </div>

    @if (session('success')) <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">{{ session('error') }}</div> @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($carBrands as $carBrand)
            <div class="bg-white rounded shadow p-6 border-t-4 border-blue-500">
                <div class="flex justify-between items-start border-b pb-4 mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">{{ $carBrand->name }}</h2>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('car-brands.edit', $carBrand->id) }}" class="text-yellow-500 hover:text-yellow-600 text-sm font-medium">แก้ไขยี่ห้อ</a>
                        <form action="{{ route('car-brands.destroy', $carBrand->id) }}" method="POST" onsubmit="return confirm('ลบยี่ห้อนี้และรุ่นรถทั้งหมด?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600 text-sm font-medium">ลบยี่ห้อ</button>
                        </form>
                    </div>
                </div>

                <!-- รายการรุ่นรถ (แสดงพร้อมปุ่มแก้ไข/ลบ) -->
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">รุ่นรถที่มีในระบบ:</h3>
                    <ul class="space-y-2">
                        @forelse($carBrand->models as $model)
                            <li class="flex justify-between items-center bg-gray-50 p-2 rounded border">
                                <div class="text-gray-700 text-sm">
                                    <span class="font-medium">{{ $model->name }}</span>
                                    <span class="text-xs text-gray-500">({{ $model->year_start ?? '-' }} - {{ $model->year_end ?? '-' }})</span>
                                </div>
                                <div class="flex space-x-2">
                                    <!-- ปุ่มแก้ไขรุ่นรถ (ใช้ป๊อปอัป prompt เพื่อความรวดเร็ว) -->
                                    <button type="button" onclick="editCarModel({{ $model->id }}, '{{ $model->name }}', {{ $model->year_start ?? 'null' }}, {{ $model->year_end ?? 'null' }})" class="text-blue-500 hover:text-blue-700 text-xs font-medium">แก้ไข</button>

                                    <!-- ฟอร์มลบรุ่นรถ -->
                                    <form action="{{ route('car-models.destroy', $model->id) }}" method="POST" onsubmit="return confirm('ลบรุ่นรถนี้?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">ลบ</button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="text-gray-400 italic text-sm">ยังไม่มีข้อมูลรุ่นรถ</li>
                        @endforelse
                    </ul>
                </div>

                <!-- ฟอร์มเพิ่มรุ่นรถ -->
                <form action="{{ route('car-models.store') }}" method="POST" class="bg-gray-100 p-3 rounded mt-4 border">
                    @csrf
                    <input type="hidden" name="car_brand_id" value="{{ $carBrand->id }}">
                    <div class="flex space-x-2">
                        <input type="text" name="name" required placeholder="ชื่อรุ่นรถ (เช่น Vigo)" class="flex-1 border p-2 rounded text-sm">
                        <input type="number" name="year_start" placeholder="ปีเริ่ม" class="w-24 border p-2 rounded text-sm">
                        <input type="number" name="year_end" placeholder="ปีสิ้นสุด" class="w-24 border p-2 rounded text-sm">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm font-medium">+ เพิ่มรุ่น</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="col-span-2 p-8 text-center text-gray-500 bg-white rounded shadow">ยังไม่มีข้อมูลยี่ห้อรถ</div>
        @endforelse
    </div>

    <!-- ฟอร์มซ่อนสำหรับส่งข้อมูลแก้ไขรุ่นรถ (ใช้ JavaScript) -->
    <form id="edit-model-form" method="POST" class="hidden">
        @csrf
        @method('PUT')
    </form>

    <!-- JavaScript สำหรับจัดการการแก้ไขรุ่นรถ -->
    <script>
        function editCarModel(id, name, yearStart, yearEnd) {
            // ใช้ prompt เพื่อรับค่าที่ต้องการแก้ไข (แบบง่าย รวดเร็ว)
            let newName = prompt("แก้ไขชื่อรุ่นรถ:", name);
            if (newName === null) return; // ถ้ากด Cancel ให้หยุด

            let newStart = prompt("แก้ไขปีเริ่มผลิต:", yearStart ?? '');
            let newEnd = prompt("แก้ไขปีสิ้นสุดการผลิต:", yearEnd ?? '');

            // กำหนดค่าให้ฟอร์มที่ซ่อนไว้
            const form = document.getElementById('edit-model-form');
            form.action = `/car-models/${id}`; // ต้องมั่นใจว่า Route ตรงกับที่กำหนด

            // สร้าง input ซ่อนเพื่อส่งค่า
            form.innerHTML = `
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="${newName}">
                <input type="hidden" name="year_start" value="${newStart}">
                <input type="hidden" name="year_end" value="${newEnd}">
            `;
            form.submit();
        }
    </script>
@endsection
