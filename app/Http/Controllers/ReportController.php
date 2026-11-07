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
    /** ต้นทุนต่อชิ้นของบรรทัดขาย (ใช้สูตรเดิมของรายงาน) */
    private const COST_SQL = 'CASE WHEN si.cost_at_sale > 0 THEN si.cost_at_sale
        ELSE COALESCE((SELECT p.cost FROM products p WHERE p.id = si.product_id), 0) END';

    public function index(Request $request)
    {
        [$startDate, $endDate, $from, $to] = $this->range($request);

        $summary = $this->summary($from, $to);

        // ยอดขายรายวัน: ยอดขายตามวันที่ขาย - ยอดรับคืนตามวันที่คืน
        $sold = Sale::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->groupBy('date')->pluck('total', 'date');

        $returned = DB::table('sale_returns')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_value) as total'))
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date')->pluck('total', 'date');

        $dailySales = $sold->keys()->merge($returned->keys())->unique()->sort()->values()
            ->map(fn ($d) => [
                'date'  => $d,
                'total' => round((float) ($sold[$d] ?? 0) - (float) ($returned[$d] ?? 0), 2),
            ]);

        // ลูกค้าซื้อมากสุด: ยอดซื้อสุทธิหลังหักของที่คืน
        $customers = Sale::select('customer_id',
            DB::raw('COUNT(*) as total_bills'),
            DB::raw('SUM(total) as total_spent'))
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->get();

        $returnedByCustomer = DB::table('sale_returns as r')
            ->join('sales as s', 's.id', '=', 'r.sale_id')
            ->where('r.status', 'completed')
            ->whereNotNull('s.customer_id')
            ->whereBetween('r.created_at', [$from, $to])
            ->groupBy('s.customer_id')
            ->selectRaw('s.customer_id, SUM(r.total_value) as total')
            ->pluck('total', 'customer_id');

        $customers->each(function ($c) use ($returnedByCustomer) {
            $c->total_spent = (float) $c->total_spent - (float) ($returnedByCustomer[$c->customer_id] ?? 0);
        });

        $topCustomers = $customers->sortByDesc('total_spent')->take(5)->values();
        $topCustomers->load('customer');

        // ประวัติการขายล่าสุด
        $recentSales = Sale::with(['customer', 'user'])
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->latest()->limit(10)->get();

        // ยอดขายแยกหมวดหมู่ (มูลค่าบรรทัดก่อนส่วนลดบิล หักด้วยของที่คืน)
        $catSold = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw("COALESCE(categories.name, 'ไม่มีหมวดหมู่') as name, SUM(sale_items.total) as total")
            ->get()->pluck('total', 'name');

        $catReturned = DB::table('sale_return_items as ri')
            ->join('sale_returns as r', 'r.id', '=', 'ri.sale_return_id')
            ->join('sale_items as si', 'si.id', '=', 'ri.sale_item_id')
            ->join('products as p', 'p.id', '=', 'ri.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('r.status', 'completed')
            ->whereBetween('r.created_at', [$from, $to])
            ->groupBy('c.id', 'c.name')
            ->selectRaw("COALESCE(c.name, 'ไม่มีหมวดหมู่') as name, SUM(ri.quantity * si.price) as total")
            ->get()->pluck('total', 'name');

        $categorySales = $catSold->keys()->merge($catReturned->keys())->unique()
            ->map(fn ($n) => [
                'name'  => $n,
                'total' => round((float) ($catSold[$n] ?? 0) - (float) ($catReturned[$n] ?? 0), 2),
            ])
            ->filter(fn ($r) => $r['total'] > 0)
            ->values();

        return view('reports.index', array_merge($summary, compact(
            'startDate', 'endDate', 'dailySales', 'topCustomers', 'recentSales', 'categorySales'
        )));
    }

    public function exportPdf(Request $request)
    {
        [$startDate, $endDate, $from, $to] = $this->range($request);

        $summary     = $this->summary($from, $to);
        $stockReport = Product::with(['category', 'brand'])->orderBy('stock')->get();

        $pdf = Pdf::setOption([
            'fontDir'     => storage_path('fonts'),
            'fontCache'   => storage_path('fonts'),
            'tempDir'     => storage_path('fonts'),
            'defaultFont' => 'sans-serif',
        ]);

        $pdf->loadView('reports.pdf', array_merge($summary, compact('startDate', 'endDate', 'stockReport')));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('report_' . $startDate . '_to_' . $endDate . '.pdf');
    }

    private function range(Request $request): array
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        return [$startDate, $endDate, $startDate . ' 00:00:00', $endDate . ' 23:59:59'];
    }

    /**
     * ตัวเลขสรุปที่ใช้ร่วมกันทั้งหน้าจอและ PDF
     * ยอดขาย/กำไร ตามวันที่ขาย, ส่วนที่คืน ตามวันที่คืน
     */
    private function summary(string $from, string $to): array
    {
        $sales = fn () => Sale::whereBetween('created_at', [$from, $to])->where('status', 'completed');

        $grossSales = (float) $sales()->sum('total');
        $totalBills = $sales()->count();

        $returnRows = fn () => DB::table('sale_return_items as ri')
            ->join('sale_returns as r', 'r.id', '=', 'ri.sale_return_id')
            ->join('sale_items as si', 'si.id', '=', 'ri.sale_item_id')
            ->where('r.status', 'completed')
            ->whereBetween('r.created_at', [$from, $to]);

        $returnedTotal = (float) DB::table('sale_returns')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_value');

        $totalSales = $grossSales - $returnedTotal;

        // กำไรขั้นต้น (ก่อนหักของที่คืน)
        $grossProfit = SaleItem::whereHas('sale', function ($q) use ($from, $to) {
            $q->whereBetween('created_at', [$from, $to])->where('status', 'completed');
        })
            ->selectRaw('SUM(sale_items.total - (CASE WHEN sale_items.cost_at_sale > 0 THEN sale_items.cost_at_sale ELSE COALESCE((SELECT p.cost FROM products p WHERE p.id = sale_items.product_id), 0) END * sale_items.quantity)) as profit')
            ->value('profit') ?? 0;

        $grossProfit -= $sales()->sum('discount'); // ส่วนลดระดับบิล

        // หักของที่คืน: ลดรายได้เต็มมูลค่า แต่คืนต้นทุนเฉพาะชิ้นที่กลับเข้าสต็อก
        $ret = $returnRows()->selectRaw(
            'COALESCE(SUM(ri.value), 0) as value,
             COALESCE(SUM(CASE WHEN ri.restock = 1 THEN ri.quantity * (' . self::COST_SQL . ') ELSE 0 END), 0) as recovered_cost'
        )->first();

        $grossProfit -= ((float) $ret->value - (float) $ret->recovered_cost);

        // สินค้าขายดี: จำนวน/รายได้สุทธิหลังหักของที่คืน
        $topProducts = SaleItem::select('product_id',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(total) as total_revenue'))
            ->whereHas('sale', function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to])->where('status', 'completed');
            })
            ->groupBy('product_id')
            ->with('product')
            ->get();

        $returnedByProduct = $returnRows()
            ->groupBy('ri.product_id')
            ->selectRaw('ri.product_id, SUM(ri.quantity) as qty, SUM(ri.quantity * si.price) as revenue')
            ->get()->keyBy('product_id');

        $topProducts->each(function ($p) use ($returnedByProduct) {
            $r = $returnedByProduct->get($p->product_id);
            $p->total_qty     = (int) $p->total_qty - (int) ($r->qty ?? 0);
            $p->total_revenue = (float) $p->total_revenue - (float) ($r->revenue ?? 0);
        });

        $topProducts = $topProducts->filter(fn ($p) => $p->total_qty > 0)
            ->sortByDesc('total_qty')->take(10)->values();

        return compact('grossSales', 'returnedTotal', 'totalSales', 'totalBills', 'grossProfit', 'topProducts');
    }
}
