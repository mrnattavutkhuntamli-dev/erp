<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // ช่วงวันที่ (Default = เดือนปัจจุบัน)
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        // 1. ยอดขายรวม
        $totalSales = Sale::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total');

        // 2. จำนวนบิล
        $totalBills = Sale::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        // 3. กำไรขั้นต้น
        $grossProfit = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                ->where('status', 'completed');
        })
            ->selectRaw('SUM(sale_items.total - (sale_items.cost_at_sale * sale_items.quantity)) as profit')
            ->value('profit') ?? 0;

        // 4. สินค้าขายดี 10 อันดับ
        $topProducts = SaleItem::select('product_id',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(total) as total_revenue'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                    ->where('status', 'completed');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->with('product')
            ->get();

        // 5. ยอดขายรายวัน
        $dailySales = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total) as total')
        )
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 6. รายงานสินค้าคงเหลือ
        $stockReport = Product::with(['category', 'brand'])
            ->orderBy('stock')
            ->get();

        // 7. ลูกค้าที่ซื้อมากที่สุด 5 อันดับ
        $topCustomers = Sale::select('customer_id',
            DB::raw('COUNT(*) as total_bills'),
            DB::raw('SUM(total) as total_spent'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->with('customer')
            ->get();

        // 8. ประวัติการขายล่าสุด 10 บิล
        $recentSales = Sale::with(['customer', 'user'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'completed')
            ->latest()
            ->limit(10)
            ->get();

        // 9. ยอดขายแยกตามหมวดหมู่สินค้า
        $categorySales = SaleItem::select(
            'products.category_id',
            DB::raw('SUM(sale_items.total) as total_revenue'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                    ->where('status', 'completed');
            })
            ->groupBy('products.category_id')
            ->with('product.category')
            ->get()
            ->map(function ($item) {
                return [
                    'name'  => $item->product->category->name ?? 'ไม่มีหมวดหมู่',
                    'total' => $item->total_revenue,
                ];
            });

        return view('reports.index', compact(
            'startDate', 'endDate', 'totalSales', 'totalBills',
            'grossProfit', 'topProducts', 'dailySales', 'stockReport',
            'topCustomers', 'recentSales', 'categorySales'
        ));
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $totalSales = Sale::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'completed')->sum('total');

        $totalBills = Sale::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'completed')->count();

        $grossProfit = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                ->where('status', 'completed');
        })
            ->selectRaw('SUM(sale_items.total - (sale_items.cost_at_sale * sale_items.quantity)) as profit')
            ->value('profit') ?? 0;

        $topProducts = SaleItem::select('product_id',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(total) as total_revenue'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                    ->where('status', 'completed');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->with('product')
            ->get();

        $stockReport = Product::with(['category', 'brand'])
            ->orderBy('stock')
            ->get();

        // โหลด View PDF
        $pdf = Pdf::setOption([
            'fontDir'     => storage_path('fonts'),
            'fontCache'   => storage_path('fonts'),
            'tempDir'     => storage_path('fonts'),
            'defaultFont' => 'sans-serif',
        ]);

        $pdf->loadView('reports.pdf', compact(
            'startDate', 'endDate', 'totalSales', 'totalBills',
            'grossProfit', 'topProducts', 'stockReport'
        ));

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('report_' . $startDate . '_to_' . $endDate . '.pdf');
    }
}
