@extends('layouts.app')
@section('title', 'รายงานภาพรวม')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- กราฟเส้น: ยอดขายรายวัน ---
            const ctxLine = document.getElementById('salesChart').getContext('2d');
            const labels = @json($dailySales->pluck('date'));
            const data = @json($dailySales->pluck('total'));

            const gradient = ctxLine.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');

            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'ยอดขาย (บาท)',
                        data: data,
                        borderColor: '#3b82f6',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 12,
                            titleFont: { size: 14, family: 'Sarabun' },
                            bodyFont: { size: 14, family: 'Sarabun' },
                            callbacks: {
                                label: (context) => `฿${context.parsed.y.toLocaleString('th-TH', { minimumFractionDigits: 2 })}`
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: 'Sarabun' } } },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { family: 'Sarabun' },
                                callback: (value) => '฿' + value.toLocaleString()
                            }
                        }
                    }
                }
            });

            // --- กราฟวงกลม: ยอดขายแยกตามหมวดหมู่ ---
            const ctxDoughnut = document.getElementById('categoryChart').getContext('2d');
            const categoryLabels = @json($categorySales->pluck('name'));
            const categoryData = @json($categorySales->pluck('total'));

            new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: [
                            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'
                        ],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { family: 'Sarabun' }, usePointStyle: true } }
                    }
                }
            });
        });
    </script>
@endpush

@section('content')
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">รายงานภาพรวมธุรกิจ</h1>
                <p class="text-sm text-slate-500 mt-1">ข้อมูลช่วง: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} ถึง {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <form action="{{ route('reports.index') }}" method="GET" class="flex gap-2 bg-white p-2 rounded-lg shadow-sm ring-1 ring-slate-200">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="border-slate-300 rounded-md text-sm p-2">
                    <span class="self-center text-slate-400">ถึง</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="border-slate-300 rounded-md text-sm p-2">
                    <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-md hover:bg-slate-900 text-sm font-medium">กรอง</button>
                </form>
                <a href="{{ route('reports.export-pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                   class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 shadow flex items-center justify-center gap-2 text-sm font-medium">
                    📄 ดาวน์โหลด PDF
                </a>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-slate-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">ยอดขายรวม</p>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">฿{{ number_format($totalSales, 2) }}</h3>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-slate-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">กำไรขั้นต้น</p>
                <h3 class="text-3xl font-bold text-emerald-600 mt-2">฿{{ number_format($grossProfit, 2) }}</h3>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-slate-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">จำนวนบิล</p>
                <h3 class="text-3xl font-bold text-slate-800 mt-2">{{ number_format($totalBills) }} <span class="text-lg font-normal text-slate-500">บิล</span></h3>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-slate-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">ลูกค้าที่ซื้อมากที่สุด</p>
                <h3 class="text-xl font-bold text-slate-800 mt-2 truncate">{{ $topCustomers->first()->customer->name ?? '-' }}</h3>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Line Chart -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow ring-1 ring-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">📈 แนวโน้มยอดขายรายวัน</h3>
                <div class="h-72">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <!-- Doughnut Chart -->
            <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">🍩 ยอดขายตามหมวดหมู่</h3>
                <div class="h-72 flex items-center justify-center">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Products -->
            <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-lg font-bold text-slate-800">🏆 สินค้าขายดี 10 อันดับ</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-slate-500 text-sm border-b border-slate-200">
                            <tr>
                                <th class="p-3 font-medium text-center w-12">#</th>
                                <th class="p-3 font-medium">สินค้า</th>
                                <th class="p-3 font-medium text-center">ขายได้</th>
                                <th class="p-3 font-medium text-right">รายได้</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($topProducts as $index => $item)
                                <tr class="hover:bg-slate-50">
                                    <td class="p-3 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                                    <td class="p-3">
                                        <div class="font-medium text-slate-800">{{ $item->product->name ?? 'สินค้าถูกลบ' }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->product->sku ?? '-' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-bold text-slate-700">{{ number_format($item->total_qty) }}</td>
                                    <td class="p-3 text-right font-bold text-emerald-600">฿{{ number_format($item->total_revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-8 text-center text-slate-400 italic">ไม่มีข้อมูลการขายในช่วงนี้</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-lg font-bold text-slate-800">👑 ลูกค้าที่ซื้อมากที่สุด 5 อันดับ</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-slate-500 text-sm border-b border-slate-200">
                            <tr>
                                <th class="p-3 font-medium text-center w-12">#</th>
                                <th class="p-3 font-medium">ลูกค้า</th>
                                <th class="p-3 font-medium text-center">จำนวนบิล</th>
                                <th class="p-3 font-medium text-right">ยอดซื้อรวม</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($topCustomers as $index => $customer)
                                <tr class="hover:bg-slate-50">
                                    <td class="p-3 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                                    <td class="p-3">
                                        <div class="font-medium text-slate-800">{{ $customer->customer->name ?? 'ลูกค้าทั่วไป' }}</div>
                                        <div class="text-xs text-slate-500">{{ $customer->customer->phone ?? '' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-bold text-slate-700">{{ $customer->total_bills }}</td>
                                    <td class="p-3 text-right font-bold text-emerald-600">฿{{ number_format($customer->total_spent, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-8 text-center text-slate-400 italic">ไม่มีข้อมูลลูกค้าในช่วงนี้</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Sales -->
        <div class="bg-white rounded-xl shadow ring-1 ring-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">🕒 ประวัติการขายล่าสุด</h3>
                <a href="{{ route('sales.history') }}" class="text-sm text-blue-600 hover:underline">ดูทั้งหมด &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-slate-500 text-sm border-b border-slate-200">
                        <tr>
                            <th class="p-3 font-medium">เลขที่ใบเสร็จ</th>
                            <th class="p-3 font-medium">วันที่</th>
                            <th class="p-3 font-medium">ลูกค้า</th>
                            <th class="p-3 font-medium">พนักงานขาย</th>
                            <th class="p-3 font-medium text-right">ยอดรวม</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($recentSales as $sale)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-medium text-blue-600">{{ $sale->invoice_no }}</td>
                                <td class="p-3 text-slate-600">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="p-3 text-slate-800">{{ $sale->customer->name ?? 'ลูกค้าทั่วไป' }}</td>
                                <td class="p-3 text-slate-600">{{ $sale->user->name }}</td>
                                <td class="p-3 text-right font-bold text-slate-800">฿{{ number_format($sale->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
