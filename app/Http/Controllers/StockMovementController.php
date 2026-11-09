<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'product_id' => 'nullable|integer|exists:products,id',
            'type'       => 'nullable|in:in,out',
        ]);

        // ✅ Default: เดือนปัจจุบัน
        $startDate = $data['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate   = $data['end_date'] ?? now()->toDateString();

        // 1. ใบรับ/ใบเบิก
        $docs = DB::table('stock_document_items as i')
            ->join('stock_documents as d', 'd.id', '=', 'i.stock_document_id')
            ->whereBetween('d.date', [$startDate, $endDate])
            ->select([
                'd.date as date',
                'd.created_at',
                DB::raw("d.type as type"),
                DB::raw("'ใบรับ/ใบเบิก' as source"),
                'd.doc_no as reference',
                'i.product_id',
                DB::raw("CASE WHEN d.type='in' THEN i.quantity ELSE -i.quantity END as quantity"),
                'i.stock_after',
                'd.note',
            ]);

        // 2. ขายหน้าร้าน (POS)
        $sales = DB::table('sale_items as i')
            ->join('sales as s', 's.id', '=', 'i.sale_id')
            ->whereBetween('s.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                DB::raw("DATE(s.created_at) as date"),
                's.created_at',
                DB::raw("'out' as type"),
                DB::raw("'ขายหน้าร้าน (POS)' as source"),
                's.invoice_no as reference',
                'i.product_id',
                DB::raw('-i.quantity as quantity'),
                'i.stock_after', // ✅ ใช้ stock_after จาก sale_items (ถ้ามี)
                DB::raw('NULL as note'),
            ]);

        // 3. ปรับปรุงสต็อก
        $adjusts = DB::table('stock_adjustments as a')
            ->whereBetween('a.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                DB::raw("DATE(a.created_at) as date"),
                'a.created_at',
                DB::raw("CASE WHEN a.type='addition' THEN 'in' ELSE 'out' END as type"),
                DB::raw("'ปรับปรุงสต็อก' as source"),
                'a.adj_no as reference',
                'a.product_id',
                DB::raw("CASE WHEN a.type='addition' THEN a.quantity ELSE -a.quantity END as quantity"),
                'a.stock_after',
                'a.reason as note',
            ]);

        // 3b. รายการย้อนกลับ: บิลขายที่ถูก void (คืนสต็อก)
        $saleVoids = DB::table('sale_items as i')
            ->join('sales as s', 's.id', '=', 'i.sale_id')
            ->where('s.status', 'voided')
            ->whereNotNull('s.voided_at')
            ->whereBetween('s.voided_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                DB::raw("DATE(s.voided_at) as date"),
                's.voided_at as created_at',
                DB::raw("'in' as type"),
                DB::raw("'ยกเลิกบิลขาย (คืนสต็อก)' as source"),
                's.invoice_no as reference',
                'i.product_id',
                DB::raw('i.quantity as quantity'),
                DB::raw('NULL as stock_after'),
                's.void_reason as note',
            ]);

        // 3c. รายการย้อนกลับ: ใบรับ/ใบเบิกที่ถูก void (ใช้ updated_at เป็นเวลายกเลิก)
        $docVoids = DB::table('stock_document_items as i')
            ->join('stock_documents as d', 'd.id', '=', 'i.stock_document_id')
            ->where('d.status', 'voided')
            ->whereBetween('d.updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                DB::raw("DATE(d.updated_at) as date"),
                'd.updated_at as created_at',
                DB::raw("CASE WHEN d.type='in' THEN 'out' ELSE 'in' END as type"),
                DB::raw("'ยกเลิกใบรับ/ใบเบิก (ย้อนสต็อก)' as source"),
                'd.doc_no as reference',
                'i.product_id',
                DB::raw("CASE WHEN d.type='in' THEN -i.quantity ELSE i.quantity END as quantity"),
                DB::raw('NULL as stock_after'),
                'd.note',
            ]);

        // 3d. รับคืนสินค้าจากลูกค้า (เฉพาะรายการที่คืนเข้าสต็อก)
        $saleReturns = DB::table('sale_return_items as ri')
            ->join('sale_returns as r', 'r.id', '=', 'ri.sale_return_id')
            ->where('ri.restock', true)
            ->whereBetween('r.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                DB::raw("DATE(r.created_at) as date"),
                'r.created_at',
                DB::raw("'in' as type"),
                DB::raw("'รับคืนสินค้า' as source"),
                'r.return_no as reference',
                'ri.product_id',
                DB::raw('ri.quantity as quantity'),
                'ri.stock_after',
                'r.reason as note',
            ]);

        // 3e. รายการย้อนกลับ: ใบรับคืนที่ถูก void (ตัดสต็อกที่รับคืนออก)
        $returnVoids = DB::table('sale_return_items as ri')
            ->join('sale_returns as r', 'r.id', '=', 'ri.sale_return_id')
            ->where('r.status', 'voided')
            ->whereNotNull('r.voided_at')
            ->where('ri.restock', true)
            ->whereBetween('r.voided_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select([
                DB::raw("DATE(r.voided_at) as date"),
                'r.voided_at as created_at',
                DB::raw("'out' as type"),
                DB::raw("'ยกเลิกใบรับคืน (ตัดสต็อกออก)' as source"),
                'r.return_no as reference',
                'ri.product_id',
                DB::raw('ri.quantity as quantity'),
                DB::raw('NULL as stock_after'),
                'r.void_reason as note',
            ]);

        // 4. UNION ALL
        $union = $docs->unionAll($sales)->unionAll($adjusts)->unionAll($saleVoids)->unionAll($docVoids)->unionAll($saleReturns)->unionAll($returnVoids);

        // 5. Query จาก UNION
        $query = DB::query()->fromSub($union, 'm')
            ->leftJoin('products as p', 'p.id', '=', 'm.product_id')
            ->select('m.*', 'p.name as product_name', 'p.sku')
            ->when($data['product_id'] ?? null, fn($q, $v) => $q->where('m.product_id', $v))
            ->when($data['type'] ?? null, fn($q, $v) => $q->where('m.type', $v))
            ->orderByDesc('m.created_at')
            ->orderByDesc('m.reference');

        $paginator = $query->paginate(30)->withQueryString();
        $products  = Product::orderBy('name')->get(['id', 'name', 'sku']);

        return view('reports.stock-movement', compact('paginator', 'products', 'startDate', 'endDate'));
    }
}
