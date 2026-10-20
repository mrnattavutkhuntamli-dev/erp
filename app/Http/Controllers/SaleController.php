<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    // 1. หน้าจอ POS
    public function index()
    {
        $products = Product::with('primaryImage')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.pos', compact('products', 'customers'));
    }

    public function history()
    {
        $sales = Sale::with(['customer', 'user'])
            ->latest()
            ->paginate(15);

        // คำนวณยอดขายรวมวันนี้ (สำหรับแสดงผล)
        $todaySales = Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total');

        return view('sales.history', compact('sales', 'todaySales'));
    }

    // 2. บันทึกการขาย (รับจาก AJAX)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'        => 'nullable|exists:customers,id',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'discount'           => 'nullable|numeric|min:0',
            'payment_method'     => 'required|in:cash,transfer,credit_card',
            'received_amount'    => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $subtotal      = 0;
                $saleItemsData = [];

                // ตรวจสอบสต็อกและคำนวณราคา
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

                // คำนวณยอดสุทธิและเงินทอน
                $discount = $validated['discount'] ?? 0;
                $total    = $subtotal - $discount;
                $received = $validated['received_amount'];
                $change   = $received - $total;

                if ($change < 0) {
                    throw new \Exception("เงินที่รับมาไม่เพียงพอ (ต้องชำระอีก " . number_format(abs($change), 2) . " บาท)");
                }

                // สร้างเลขที่ใบเสร็จ
                $dateStr     = date('Ymd');
                $countToday  = Sale::whereDate('created_at', today())->count() + 1;
                $invoiceNo   = 'INV-' . $dateStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

                // บันทึกหัวใบเสร็จ
                $sale = Sale::create([
                    'invoice_no'      => $invoiceNo,
                    'customer_id'     => $validated['customer_id'],
                    'user_id'         => auth()->id(),
                    'subtotal'        => $subtotal,
                    'discount'        => $discount,
                    'total'           => $total,
                    'payment_method'  => $validated['payment_method'],
                    'payment_status'  => 'paid',
                    'paid_amount'     => $total,
                    'received_amount' => $received,
                    'change_amount'   => $change,
                ]);

                // บันทึกรายการและตัดสต็อก
                foreach ($saleItemsData as $data) {
                    $sale->items()->create([
                        'product_id' => $data['product']->id,
                        'quantity'   => $data['quantity'],
                        'price'      => $data['price'],
                        'total'      => $data['total'],
                    ]);

                    $data['product']->decrement('stock', $data['quantity']);
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

    // 3. หน้าแสดงใบเสร็จ
    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'user', 'customer']);
        return view('sales.receipt', compact('sale'));
    }

    // 4. ยกเลิกใบเสร็จ
    public function destroy(Sale $sale)
    {
        if ($sale->status === 'voided') {
            return redirect()->route('sales.index')->with('error', 'ใบเสร็จนี้ถูกยกเลิกแล้ว');
        }

        try {
            return DB::transaction(function () use ($sale) {
                foreach ($sale->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);
                    $product->increment('stock', $item->quantity);
                }

                $sale->update(['status' => 'voided']);

                return redirect()->route('sales.index')->with('success', 'ยกเลิกใบเสร็จ ' . $sale->invoice_no . ' สำเร็จ');
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
