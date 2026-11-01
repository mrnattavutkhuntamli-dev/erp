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
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        // ช่วงวันที่ (Default = เดือนปัจจุบัน)
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        // 1. ยอดขายรวม
        $totalSales = Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->sum('total');

        // 2. จำนวนบิล
        $totalBills = Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->count();

        // 3. กำไรขั้นต้น
        $grossProfit = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->where('status', 'completed');
        })
            ->selectRaw('SUM(sale_items.total - (CASE WHEN sale_items.cost_at_sale > 0 THEN sale_items.cost_at_sale ELSE COALESCE((SELECT p.cost FROM products p WHERE p.id = sale_items.product_id), 0) END * sale_items.quantity)) as profit')
            ->value('profit') ?? 0;

        // หักส่วนลดระดับบิล
        $grossProfit -= Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')->sum('discount');

        // 4. สินค้าขายดี 10 อันดับ
        $topProducts = SaleItem::select('product_id',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(total) as total_revenue'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
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
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 7. ลูกค้าที่ซื้อมากที่สุด 5 อันดับ
        $topCustomers = Sale::select('customer_id',
            DB::raw('COUNT(*) as total_bills'),
            DB::raw('SUM(total) as total_spent'))
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->with('customer')
            ->get();

        // 8. ประวัติการขายล่าสุด 10 บิล
        $recentSales = Sale::with(['customer', 'user'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->latest()
            ->limit(10)
            ->get();

        // 9. ยอดขายแยกตามหมวดหมู่สินค้า
        $categorySales = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw("COALESCE(categories.name, 'ไม่มีหมวดหมู่') as name, SUM(sale_items.total) as total")
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'total' => $r->total]);

        return view('reports.index', compact(
            'startDate', 'endDate', 'totalSales', 'totalBills',
            'grossProfit', 'topProducts', 'dailySales',
            'topCustomers', 'recentSales', 'categorySales'
        ));
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $totalSales = Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')->sum('total');

        $totalBills = Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')->count();

        $grossProfit = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->where('status', 'completed');
        })
            ->selectRaw('SUM(sale_items.total - (CASE WHEN sale_items.cost_at_sale > 0 THEN sale_items.cost_at_sale ELSE COALESCE((SELECT p.cost FROM products p WHERE p.id = sale_items.product_id), 0) END * sale_items.quantity)) as profit')
            ->value('profit') ?? 0;

        // หักส่วนลดระดับบิล
        $grossProfit -= Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')->sum('discount');

        $topProducts = SaleItem::select('product_id',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(total) as total_revenue'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
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
