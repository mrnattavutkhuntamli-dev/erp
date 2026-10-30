<?php
namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        // ไม่ให้เข้าหน้า POS ถ้ายังไม่ถึงกะ/หมดกะ พาไปหน้าเปิด-ปิดกะพร้อมบอกเหตุผล
        if ($reason = CashierShift::sellBlockReason(auth()->user())) {
            return redirect()->route('cashier-shifts.index')->with('error', $reason);
        }

        $categories = Category::orderBy('name')->get();

        $products = Product::with('primaryImage:id,product_id,image_path')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock', 'category_id']);

        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.pos', compact('products', 'customers', 'categories'));
    }

    public function history()
    {
        $sales = Sale::with(['customer', 'user'])
            ->latest()
            ->paginate(15);

        $todaySales = Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total');

        return view('sales.history', compact('sales', 'todaySales'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'        => 'nullable|exists:customers,id',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'discount'           => 'nullable|numeric|min:0',
            'payment_method'     => 'required|in:cash,transfer,credit_card,credit',
            'received_amount'    => 'nullable|numeric|min:0',
            'idempotency_key'    => 'nullable|uuid',
        ]);
        if ($validated['payment_method'] === 'credit' && empty($validated['customer_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถขายเชื่อให้ลูกค้าทั่วไปได้ กรุณาเลือกลูกค้าที่มีข้อมูล',
            ], 422);
        }

        // ต้องอยู่ในช่วงกะของตัวเองจึงขายได้ (ยกเว้น admin)
        if ($reason = CashierShift::sellBlockReason(auth()->user())) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }
        try {
            return DB::transaction(function () use ($validated) {
                // ✅ ตรวจ Idempotency (ป้องกันกดซ้ำ)
                if (! empty($validated['idempotency_key'])) {
                    $existing = Sale::where('idempotency_key', $validated['idempotency_key'])->first();
                    if ($existing) {
                        return response()->json([
                            'success'      => true,
                            'sale_id'      => $existing->id,
                            'redirect_url' => route('sales.show', $existing->id),
                        ]);
                    }
                }

                $subtotal      = 0;
                $saleItemsData = [];

                // รวมสินค้าซ้ำ + เรียงตาม id (กัน deadlock / ตัดสต็อกเกิน)
                $lines = collect($validated['items'])
                    ->groupBy('product_id')
                    ->map(fn ($g, $id) => ['product_id' => (int) $id, 'quantity' => (int) $g->sum('quantity')])
                    ->sortKeys()
                    ->values();

                foreach ($lines as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    if (! $product->is_active) {
                        throw new \DomainException("สินค้า '{$product->name}' ถูกปิดการขาย");
                    }
                    if ($product->stock < $item['quantity']) {
                        throw new \DomainException("สินค้า '{$product->name}' มีสต็อกไม่พอ (เหลือ {$product->stock})");
                    }

                    $itemTotal = $product->price * $item['quantity'];
                    $subtotal  += $itemTotal;

                    $saleItemsData[] = [
                        'product'      => $product,
                        'quantity'     => $item['quantity'],
                        'price'        => $product->price,
                        'cost_at_sale' => $product->cost,
                        'total'        => $itemTotal,
                    ];
                }

                $discount = $validated['discount'] ?? 0;
                if ($discount > $subtotal) {
                    throw new \DomainException("ส่วนลด (฿" . number_format($discount, 2) . ") เกินยอดรวม (฿" . number_format($subtotal, 2) . ")");
                }
                $total = $subtotal - $discount;

                // ✅ ตรวจสอบ "ขายเชื่อ"
                $isCredit = $validated['payment_method'] === 'credit';

                if ($isCredit) {
                    $received = 0;
                    $change   = 0;
                } else {
                    $received = $validated['received_amount'] ?? 0;
                    $change   = $received - $total;

                    if ($change < 0) {
                        throw new \DomainException("เงินที่รับมาไม่เพียงพอ (ต้องชำระอีก " . number_format(abs($change), 2) . " บาท)");
                    }
                }

                $sale = Sale::create([
                    'invoice_no'      => 'TMP-' . Str::uuid(),
                    'customer_id'     => $validated['customer_id'] ?? null,
                    'user_id'         => auth()->id(),
                    'subtotal'        => $subtotal,
                    'discount'        => $discount,
                    'total'           => $total,
                    'payment_method'  => $validated['payment_method'],
                    'payment_status'  => $isCredit ? 'unpaid' : 'paid',
                    'paid_amount'     => $isCredit ? 0 : $total,
                    'received_amount' => $received,
                    'change_amount'   => $change,
                    'status'          => 'completed',
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                ]);
                // ✅ อัปเดตเลข INV จริงจาก ID
                $sale->update([
                    'invoice_no' => 'INV-' . date('Ymd') . '-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                ]);

                foreach ($saleItemsData as $data) {
                    $data['product']->decrement('stock', $data['quantity']);
                    $newStock = $data['product']->fresh()->stock;
                    $sale->items()->create([
                        'product_id'   => $data['product']->id,
                        'quantity'     => $data['quantity'],
                        'price'        => $data['price'],
                        'cost_at_sale' => $data['cost_at_sale'],
                        'total'        => $data['total'],
                        'stock_after'  => $newStock,
                    ]);
                }

                return response()->json([
                    'success'      => true,
                    'sale_id'      => $sale->id,
                    'redirect_url' => route('sales.show', $sale->id),
                ]);
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'], 500);
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'user', 'customer', 'voidedBy']);
        return view('sales.receipt', compact('sale'));
    }

    public function destroy(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'void_reason' => 'required|string|max:500',
        ]);

        try {
            return DB::transaction(function () use ($sale, $validated) {
                $locked = Sale::lockForUpdate()->findOrFail($sale->id);

                if ($locked->status === 'voided') {
                    throw new \DomainException('ใบเสร็จนี้ถูกยกเลิกแล้ว');
                }

                $activeReceipt = $locked->receipts()->where('status', 'active')->first();
                if ($activeReceipt) {
                    throw new \DomainException(
                        'ไม่สามารถยกเลิกบิลนี้ได้ เพราะมีการรับชำระเงินแล้ว กรุณายกเลิกใบรับเงิน ' . $activeReceipt->receipt_no . ' ก่อน'
                    );
                }

                foreach ($locked->items()->orderBy('product_id')->get() as $item) {
                    Product::lockForUpdate()->findOrFail($item->product_id)
                        ->increment('stock', $item->quantity);
                }

                $locked->update([
                    'status'      => 'voided',
                    'voided_at'   => now(),
                    'voided_by'   => auth()->id(),
                    'void_reason' => $validated['void_reason'],
                ]);

                return redirect()->route('sales.history')
                    ->with('success', 'ยกเลิกใบเสร็จ ' . $locked->invoice_no . ' สำเร็จ');
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        }
    }
}
