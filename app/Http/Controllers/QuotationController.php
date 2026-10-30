<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class QuotationController extends Controller
{
    // 1. หน้ารายการใบเสนอราคา
    public function index()
    {
        $quotations = Quotation::with(['customer', 'user'])
            ->latest()
            ->paginate(15);

        return view('quotations.index', compact('quotations'));
    }

    // 2. หน้าสร้างใบเสนอราคา
    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('quotations.create', compact('customers', 'products'));
    }

    // 3. บันทึกใบเสนอราคา
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'        => 'nullable|exists:customers,id',
            'quote_date'         => 'required|date',
            'valid_until'        => 'required|date|after_or_equal:quote_date',
            'discount'           => 'nullable|numeric|min:0',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                // คำนวณยอดรวม
                $subtotal = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['price']);
                $discount = $validated['discount'] ?? 0;
                if ($discount > $subtotal) {
                    throw new \Exception("ส่วนลดเกินยอดรวม");
                }
                $total = $subtotal - $discount;

                // สร้างเลขที่ใบเสนอราคา
                $dateStr    = date('Ymd');
                $countToday = Quotation::whereDate('created_at', today())->count() + 1;
                $quoteNo    = 'QT-' . $dateStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

                // บันทึกหัวเอกสาร
                $quotation = Quotation::create([
                    'quote_no'    => $quoteNo,
                    'customer_id' => $validated['customer_id'],
                    'user_id'     => auth()->id(),
                    'quote_date'  => $validated['quote_date'],
                    'valid_until' => $validated['valid_until'],
                    'subtotal'    => $subtotal,
                    'discount'    => $discount,
                    'total'       => $total,
                    'status'      => 'draft',
                    'note'        => $validated['note'] ?? null,
                ]);

                // บันทึกรายการสินค้า
                foreach ($validated['items'] as $item) {
                    $quotation->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'],
                        'total'      => $item['quantity'] * $item['price'],
                    ]);
                }

                return redirect()->route('quotations.show', $quotation->id)
                    ->with('success', 'สร้างใบเสนอราคา ' . $quoteNo . ' สำเร็จ!');
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    // 4. หน้ารายละเอียด
    public function show(Quotation $quotation)
    {
        $quotation->load(['items.product', 'customer', 'user']);
        return view('quotations.show', compact('quotation'));
    }

    // 5. อัปเดตสถานะ (เช่น sent, rejected)
    public function updateStatus(Request $request, Quotation $quotation)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,rejected',
        ]);

        $quotation->update(['status' => $request->status]);

        return back()->with('success', 'อัปเดตสถานะสำเร็จ!');
    }
    /*
    public function convertToSale(Quotation $quotation)
    {


        // ป้องกันการแปลงซ้ำ
        if ($quotation->status === 'accepted') {
            return back()->with('error', 'ใบเสนอราคานี้ถูกแปลงเป็นใบเสร็จแล้ว');
        }

        try {
            return DB::transaction(function () use ($quotation) {
                // ตรวจสอบสต็อกก่อน
                foreach ($quotation->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);
                    if ($product->stock < $item->quantity) {
                        throw new \Exception("สินค้า '{$product->name}' มีสต็อกไม่พอ (เหลือ {$product->stock})");
                    }
                }

                // สร้างเลขที่ใบเสร็จ
                $dateStr    = date('Ymd');
                $countToday = Sale::whereDate('created_at', today())->count() + 1;
                $invoiceNo  = 'INV-' . $dateStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

                // สร้าง Sale
                $sale = Sale::create([
                    'invoice_no'      => $invoiceNo,
                    'customer_id'     => $quotation->customer_id,
                    'user_id'         => auth()->id(),
                    'subtotal'        => $quotation->subtotal,
                    'discount'        => $quotation->discount,
                    'total'           => $quotation->total,
                    'payment_method'  => 'cash', // ค่าเริ่มต้น
                    'received_amount' => $quotation->total,
                    'change_amount'   => 0,
                    'status'          => 'completed',
                ]);

                // บันทึกรายการ + ตัดสต็อก
                foreach ($quotation->items as $item) {
                    $sale->items()->create([
                        'product_id' => $item->product_id,
                        'quantity'   => $item->quantity,
                        'price'      => $item->price,
                        'total'      => $item->total,
                    ]);

                    Product::find($item->product_id)->decrement('stock', $item->quantity);
                }

                // อัปเดตสถานะใบเสนอราคา
                $quotation->update(['status' => 'accepted']);

                return redirect()->route('sales.show', $sale->id)
                    ->with('success', 'แปลงเป็นใบเสร็จ ' . $invoiceNo . ' สำเร็จ!');
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    */
        public function convertToSale(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'payment_method'  => 'required|in:cash,transfer,credit_card,credit',
            'received_amount' => 'nullable|numeric|min:0',
            'idempotency_key' => 'nullable|uuid',
        ]);

        try {
            return DB::transaction(function () use ($validated, $quotation) {
                // ล็อกใบเสนอราคาก่อนเช็คสถานะ กันกดแปลงซ้ำพร้อมกัน
                $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);

                // กดซ้ำด้วย key เดิม → พาไปที่บิลเดิม
                if (! empty($validated['idempotency_key'])) {
                    $existing = Sale::where('idempotency_key', $validated['idempotency_key'])->first();
                    if ($existing) {
                        return redirect()->route('sales.show', $existing->id)
                            ->with('success', 'รายการนี้ถูกบันทึกไปแล้ว');
                    }
                }

                if (! in_array($quotation->status, ['draft', 'sent'], true)) {
                    throw new \DomainException('ใบเสนอราคานี้แปลงเป็นใบเสร็จไม่ได้ (สถานะ: ' . $quotation->status . ')');
                }

                $isCredit = $validated['payment_method'] === 'credit';
                if ($isCredit && empty($quotation->customer_id)) {
                    throw new \DomainException('ไม่สามารถขายเชื่อให้ลูกค้าทั่วไปได้ กรุณาระบุลูกค้าในใบเสนอราคา');
                }

                $total = (float) $quotation->total;
                if ($isCredit) {
                    $received = 0;
                    $change   = 0;
                } else {
                    $received = $validated['received_amount'] ?? $total;
                    if ((int) round($received * 100) < (int) round($total * 100)) {
                        throw new \DomainException('เงินที่รับมาไม่เพียงพอ (ต้องชำระอีก ' . number_format($total - $received, 2) . ' บาท)');
                    }
                    $change = round($received - $total, 2);
                }

                // ล็อกสินค้าเรียงตาม id (กัน deadlock) แล้วตรวจสต็อก
                $items    = $quotation->items()->orderBy('product_id')->get();
                $products = [];
                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);
                    if ($product->stock < $item->quantity) {
                        throw new \DomainException("สินค้า '{$product->name}' มีสต็อกไม่พอ (เหลือ {$product->stock})");
                    }
                    $products[$item->id] = $product;
                }

                // เลขชั่วคราว (ไม่ชน) แล้วอัปเดตจาก id จริง — เหมือน Receipt
                $sale = Sale::create([
                    'invoice_no'      => 'TMP-' . Str::uuid(),
                    'customer_id'     => $quotation->customer_id,
                    'user_id'         => auth()->id(),
                    'subtotal'        => $quotation->subtotal,
                    'discount'        => $quotation->discount,
                    'total'           => $quotation->total,
                    'payment_method'  => $validated['payment_method'],
                    'payment_status'  => $isCredit ? 'unpaid' : 'paid',
                    'paid_amount'     => $isCredit ? 0 : $quotation->total,
                    'received_amount' => $received,
                    'change_amount'   => $change,
                    'status'          => 'completed',
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                ]);
                $sale->update([
                    'invoice_no' => 'INV-' . date('Ymd') . '-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                ]);

                foreach ($items as $item) {
                    $product = $products[$item->id];
                    $product->decrement('stock', $item->quantity);

                    $sale->items()->create([
                        'product_id'   => $item->product_id,
                        'quantity'     => $item->quantity,
                        'price'        => $item->price,
                        'cost_at_sale' => $product->cost,
                        'total'        => $item->total,
                        'stock_after'  => $product->fresh()->stock,
                    ]);
                }

                $quotation->update(['status' => 'accepted']);

                return redirect()->route('sales.show', $sale->id)
                    ->with('success', 'แปลงเป็นใบเสร็จ ' . $sale->invoice_no . ' สำเร็จ!');
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'ไม่สามารถแปลงเป็นใบเสร็จได้ กรุณาลองใหม่อีกครั้ง');
        }
    }

    // 7. Export PDF ใบเสนอราคา
    public function exportPdf(Quotation $quotation)
    {
        $quotation->load(['items.product', 'customer', 'user']);

        $pdf = Pdf::setOption([
            'fontDir'     => storage_path('fonts'),
            'fontCache'   => storage_path('fonts'),
            'tempDir'     => storage_path('fonts'),
            'defaultFont' => 'sans-serif',
        ]);

        $pdf->loadView('quotations.pdf', compact('quotation'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('quotation_' . $quotation->quote_no . '.pdf');
    }
}
