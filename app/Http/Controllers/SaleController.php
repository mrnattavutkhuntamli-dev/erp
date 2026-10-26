<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
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
                'message' => 'ไม่สามารถขายเชื่อให้ลูกค้าทั่วไปได้ กรุณาเลือกลูกค้าที่มีข้อมูล'
            ], 422);
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

                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("สินค้า '{$product->name}' มีสต็อกไม่พอ (เหลือ {$product->stock})");
                    }

                    $itemTotal = $product->price * $item['quantity'];
                    $subtotal  += $itemTotal;

                    $saleItemsData[] = [
                        'product'  => $product,
                        'quantity' => $item['quantity'],
                        'price'    => $product->price,
                        'total'    => $itemTotal,
                    ];
                }

                $discount = $validated['discount'] ?? 0;
                $total    = $subtotal - $discount;

                // ✅ ตรวจสอบ "ขายเชื่อ"
                $isCredit = $validated['payment_method'] === 'credit';

                if ($isCredit) {
                    $received = 0;
                    $change   = 0;
                } else {
                    $received = $validated['received_amount'] ?? 0;
                    $change   = $received - $total;

                    if ($change < 0) {
                        throw new \Exception("เงินที่รับมาไม่เพียงพอ (ต้องชำระอีก " . number_format(abs($change), 2) . " บาท)");
                    }
                }

                $dateStr    = date('Ymd');
                $countToday = Sale::whereDate('created_at', today())->count() + 1;
                $invoiceNo  = 'INV-' . $dateStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

                $sale = Sale::create([
                    'invoice_no'      => $invoiceNo,
                    'customer_id'     => $validated['customer_id'],
                    'user_id'         => auth()->id(),
                    'subtotal'        => $subtotal,
                    'discount'        => $discount,
                    'total'           => $total,
                    'payment_method'  => $isCredit ? 'cash' : $validated['payment_method'],
                    'payment_status'  => $isCredit ? 'unpaid' : 'paid',
                    'paid_amount'     => $isCredit ? 0 : $total,
                    'received_amount' => $received,
                    'change_amount'   => $change,
                    'status'          => 'completed',
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                ]);

                foreach ($saleItemsData as $data) {
                    $data['product']->decrement('stock', $data['quantity']);
                    $newStock = $data['product']->fresh()->stock;
                    $sale->items()->create([
                        'product_id' => $data['product']->id,
                        'quantity'   => $data['quantity'],
                        'price'      => $data['price'],
                        'total'      => $data['total'],
                        'stock_after' => $newStock,
                    ]);
                }

                return response()->json([
                    'success'      => true,
                    'sale_id'      => $sale->id,
                    'redirect_url' => route('sales.show', $sale->id),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'user', 'customer', 'voidedBy']);
        return view('sales.receipt', compact('sale'));
    }

    public function destroy(Request $request, Sale $sale)
    {
        if ($sale->status === 'voided') {
            return back()->with('error', 'ใบเสร็จนี้ถูกยกเลิกแล้ว');
        }

        $validated = $request->validate([
            'void_reason' => 'required|string|max:500',
        ]);

        try {
            return DB::transaction(function () use ($sale, $validated) {
                foreach ($sale->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);
                    $product->increment('stock', $item->quantity);
                }

                $sale->update([
                    'status'      => 'voided',
                    'voided_at'   => now(),
                    'voided_by'   => auth()->id(),
                    'void_reason' => $validated['void_reason'],
                ]);

                return redirect()->route('sales.history')
                    ->with('success', 'ยกเลิกใบเสร็จ ' . $sale->invoice_no . ' สำเร็จ');
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
