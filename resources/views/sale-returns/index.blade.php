@extends('layouts.app')

@section('title', 'ใบรับคืนสินค้า')

@section('content')
    <section class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-4">
            <h1 class="text-lg font-semibold text-slate-900">ใบรับคืนสินค้า</h1>
            <p class="text-sm text-slate-500">สร้างใบรับคืนได้จากปุ่ม "คืนสินค้า" ในหน้าประวัติการขาย</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">เลขที่ใบรับคืน</th>
                        <th class="px-5 py-3 font-medium">วันที่/เวลา</th>
                        <th class="px-5 py-3 font-medium">บิลอ้างอิง</th>
                        <th class="px-5 py-3 font-medium">ลูกค้า</th>
                        <th class="px-5 py-3 font-medium text-right">มูลค่ารับคืน</th>
                        <th class="px-5 py-3 font-medium text-right">คืนเงิน</th>
                        <th class="px-5 py-3 font-medium">พนักงาน</th>
                        <th class="px-5 py-3 font-medium text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($returns as $r)
                        @php $voided = $r->status !== 'completed'; @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ $voided ? 'bg-red-50/50' : '' }}">
                            <td class="px-5 py-3">
                                <div class="font-medium {{ $voided ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $r->return_no }}</div>
                                @if ($voided)
                                    <span class="mt-0.5 inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">ยกเลิกแล้ว</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $r->sale->invoice_no ?? '-' }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $r->sale->customer->name ?? 'ลูกค้าทั่วไป' }}</td>
                            <td class="px-5 py-3 text-right font-medium text-slate-900">฿{{ number_format($r->total_value, 2) }}</td>
                            <td class="px-5 py-3 text-right text-slate-700">
                                @if ($r->refund_amount > 0)
                                    ฿{{ number_format($r->refund_amount, 2) }}
                                    <span class="block text-xs text-slate-400">{{ $r->refund_method === 'cash' ? 'เงินสด' : 'โอนเงิน' }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $r->user->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('sale-returns.show', $r->id) }}"
                                   class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors">
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center">
                                <p class="font-medium text-slate-900">ยังไม่มีใบรับคืนสินค้า</p>
                                <p class="text-sm text-slate-500">เริ่มจากหน้าประวัติการขาย แล้วกด "คืนสินค้า" ที่บิลที่ต้องการ</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($returns->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $returns->withQueryString()->links() }}
            </div>
        @endif
    </section>
@endsection
