@extends('layouts.app')
@section('title', 'ประวัติใบรับ/ใบเบิก')
@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">รายการเอกสารคลังสินค้า</h1>
        <div class="space-x-2">
            <a href="{{ route('stock-documents.create', ['type' => 'in']) }}"
                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 shadow">+ ใบรับสินค้า (IN)</a>
            <a href="{{ route('stock-documents.create', ['type' => 'out']) }}"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 shadow">- ใบเบิกสินค้า (OUT)</a>
        </div>
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
                    <th class="p-4">เลขที่เอกสาร</th>
                    <th class="p-4">ประเภท</th>
                    <th class="p-4">วันที่</th>
                    <th class="p-4">ผู้รับ/ผู้ส่ง</th>
                    <th class="p-4">พนักงาน</th>
                    <th class="p-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <!-- เพิ่มเงื่อนไขพื้นหลังสีแดงอ่อนและตัวหนังสือจาง ถ้าเอกสารถูกยกเลิก -->
                    <tr class="hover:bg-gray-50 border-b {{ $doc->status === 'voided' ? 'bg-red-50 opacity-60' : '' }}">
                        <td class="p-4 font-medium text-blue-600">
                            {{ $doc->doc_no }}
                            @if($doc->status === 'voided')
                                <span class="text-red-500 text-xs block">(ยกเลิกแล้ว)</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if ($doc->type == 'in')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">รับเข้า (IN)</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">เบิกออก (OUT)</span>
                            @endif
                        </td>
                        <td class="p-4 text-gray-600">{{ \Carbon\Carbon::parse($doc->date)->format('d/m/Y') }}</td>
                        <td class="p-4 text-gray-600">
                            @if ($doc->type == 'in')
                                {{ $doc->supplier->name ?? '-' }}
                            @else
                                {{ $doc->receiver_name ?? '-' }}
                            @endif
                        </td>
                        <td class="p-4 text-gray-600">{{ $doc->user->name }}</td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <!-- ปุ่มดูรายละเอียด -->
                                <a href="{{ route('stock-documents.show', $doc->id) }}"
                                    class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 text-sm">
                                    ดู
                                </a>

                                @if ($doc->status !== 'voided')
                                    <!-- ปุ่มแก้ไข -->
                                    <a href="{{ route('stock-documents.edit', $doc->id) }}"
                                        class="bg-yellow-400 text-white px-3 py-1 rounded hover:bg-yellow-500 text-sm">
                                        แก้ไข
                                    </a>

                                    <!-- ปุ่มยกเลิก -->
                                    <form action="{{ route('stock-documents.destroy', $doc->id) }}" method="POST"
                                        onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกเอกสารนี้? สต็อกจะถูกคืนกลับอัตโนมัติ');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
                                            ยกเลิก
                                        </button>
                                    </form>
                                @else
                                    <span class="text-red-500 text-sm font-bold px-3 py-1 bg-red-100 rounded">ยกเลิกแล้ว</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-500">ยังไม่มีประวัติเอกสาร</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $documents->links() }}</div>
    </div>
@endsection
