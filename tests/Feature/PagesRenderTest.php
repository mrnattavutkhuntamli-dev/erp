<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: หน้าหลักๆ ต้องเรนเดอร์ได้ (กัน Blade/Route error หลังแก้ view)
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_key_pages_render_for_admin(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin->fresh());

        User::factory()->create()->assignRole('cashier'); // มีผู้ใช้อื่นให้เห็นปุ่มปิดใช้งาน

        $supplier = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $customer = Customer::create(['name' => 'Cust', 'is_active' => true]);
        $product  = Product::create(['sku' => 'S1', 'name' => 'P', 'cost' => 10, 'price' => 20, 'stock' => 5, 'is_active' => true]);

        $pending = PurchaseOrder::create(['po_no' => 'PO-1', 'supplier_id' => $supplier->id, 'user_id' => $admin->id,
            'order_date' => now()->toDateString(), 'status' => 'pending', 'total_amount' => 100]);
        $received = PurchaseOrder::create(['po_no' => 'PO-2', 'supplier_id' => $supplier->id, 'user_id' => $admin->id,
            'order_date' => now()->subDays(40)->toDateString(), 'status' => 'received', 'total_amount' => 300, 'paid_amount' => 100]);
        foreach ([$pending, $received] as $po) {
            $po->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 100, 'total' => 100]);
        }

        $q = Quotation::create(['quote_no' => 'QT-1', 'user_id' => $admin->id, 'customer_id' => $customer->id,
            'quote_date' => now()->toDateString(), 'valid_until' => now()->addDays(3)->toDateString(),
            'subtotal' => 20, 'discount' => 0, 'total' => 20, 'status' => 'sent']);
        $q->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 20, 'total' => 20]);

        $this->postJson(route('sales.store'), [
            'customer_id' => $customer->id, 'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'credit',
        ])->assertOk();

        $urls = [
            route('dashboard'),
            route('products.index'), route('products.create'), route('products.edit', $product),
            route('quotations.index'), route('quotations.create'), route('quotations.show', $q),
            route('users.index'), route('users.create'),
            route('expenses.index'),
            route('purchase-orders.index'), route('purchase-orders.create'),
            route('purchase-orders.show', $pending), route('purchase-orders.show', $received),
            route('reports.index'), route('reports.ar-aging'), route('reports.ap-aging'),
            route('reports.stock-movement'),
            route('cashier-shifts.index'), route('shift-schedules.index'),
            route('car-brands.index'), route('categories.index'),
            route('sales.history'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        // หน้า PO ที่รับของแล้วต้องมีส่วนจ่ายเงิน, หน้าผู้ใช้ต้องมีปุ่มปิดใช้งาน
        $this->get(route('purchase-orders.show', $received))->assertSee('บันทึกการจ่ายเงิน')->assertSee('ค้างจ่าย');
        $this->get(route('users.index'))->assertSee('ปิดใช้งาน');
        $this->get(route('expenses.index'))->assertSee('จ่ายจากเงินสดในลิ้นชักแคชเชียร์');
    }

    public function test_pdf_report_views_render(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin->fresh());

        foreach ([route('reports.ar-aging.export-pdf'), route('reports.ap-aging.export-pdf')] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
