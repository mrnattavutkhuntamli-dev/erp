<?php
namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Setting;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests รอบที่ 2: ปิดกะ/คืนเงินสด, ยกเลิกใบรับคืน, ลูกหนี้หักของคืน,
 * รับเงินสดต้องมีกะ, ลบผู้ใช้/ซัพพลายเออร์, ข้อมูลบริษัท, เมนูฝ่ายบัญชี
 */
class RoundTwoFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = $this->makeUser('admin');
        $this->actingAs($this->admin);
        config(['pos.require_open_shift' => false]);
    }

    // ---------- helpers ----------

    private function makeUser(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u->fresh();
    }

    private function product(array $o = []): Product
    {
        return Product::create(array_merge([
            'sku'   => 'T-' . uniqid(), 'name' => 'Brake Pad', 'cost' => 100, 'price' => 200,
            'stock' => 10, 'is_active'         => true,
        ], $o));
    }

    private function cashSale(Product $p, int $qty): Sale
    {
        $this->postJson(route('sales.store'), [
            'items'           => [['product_id' => $p->id, 'quantity' => $qty]],
            'payment_method'  => 'cash',
            'received_amount' => 100000,
        ])->assertOk();

        return Sale::latest('id')->first();
    }

    private function creditSale(Product $p, Customer $c, int $qty): Sale
    {
        $this->postJson(route('sales.store'), [
            'customer_id'    => $c->id,
            'items'          => [['product_id' => $p->id, 'quantity' => $qty]],
            'payment_method' => 'credit',
        ])->assertOk();

        return Sale::latest('id')->first();
    }

    private function returnItem(Sale $sale, int $qty, array $extra = []): void
    {
        $item = $sale->items()->first();
        $this->post(route('sale-returns.store', $sale), array_merge([
            'items'         => [['sale_item_id' => $item->id, 'quantity' => $qty, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'ทดสอบ',
        ], $extra))->assertSessionHasNoErrors();
    }

    // ---------- A. ปิดกะต้องบันทึกยอดคืนเงินสด ----------

    public function test_closing_shift_stores_cash_refunds(): void
    {
        $shift = CashierShift::create([
            'user_id' => $this->admin->id, 'opened_at' => now()->subHour(),
            'opening_cash' => 0, 'status' => 'open',
        ]);

        $p    = $this->product();
        $sale = $this->cashSale($p, 2);   // 400
        $this->returnItem($sale, 1);      // คืนเงินสด 200

        $this->post(route('cashier-shifts.close', $shift), ['closing_cash' => 200]);

        $this->assertEquals(200, $shift->fresh()->total_cash_refunds);
    }

    // ---------- B. ลูกหนี้หักของที่รับคืน ----------

    public function test_ar_aging_deducts_returned_goods(): void
    {
        $c    = Customer::create(['name' => 'Cust', 'is_active' => true]);
        $p    = $this->product();
        $sale = $this->creditSale($p, $c, 2); // ค้าง 400
        $this->returnItem($sale, 1, ['refund_method' => null]); // คืนของ 200 ยังไม่เคยจ่าย → ไม่ต้องคืนเงิน

        $row = $this->get(route('reports.ar-aging'))->assertOk()->viewData('agingData')[$c->id]['invoices'][0];
        $this->assertEquals(200, $row['due']);
    }

    // ---------- C. ยกเลิกใบรับคืน ----------

    public function test_void_return_reverses_stock_and_sale_amounts(): void
    {
        $p    = $this->product(['stock' => 10]);
        $sale = $this->cashSale($p, 4); // สต็อก 6
        $this->returnItem($sale, 1);    // สต็อก 7
        $this->assertSame(7, $p->fresh()->stock);

        $return = SaleReturn::first();
        $this->delete(route('sale-returns.destroy', $return), ['void_reason' => 'คืนผิดบิล'])
            ->assertSessionHasNoErrors();

        $this->assertSame(6, $p->fresh()->stock);
        $return->refresh();
        $this->assertSame('voided', $return->status);
        $this->assertSame('คืนผิดบิล', $return->void_reason);
        $this->assertEquals(0, $sale->fresh()->returned_amount);
        $this->assertEquals(0, $sale->fresh()->refunded_amount);
        $this->assertSame('paid', $sale->fresh()->payment_status);

        // ยกเลิกซ้ำไม่ได้ และสต็อกไม่ถูกตัดซ้ำ
        $this->delete(route('sale-returns.destroy', $return), ['void_reason' => 'อีกครั้ง'])
            ->assertSessionHas('error');
        $this->assertSame(6, $p->fresh()->stock);

        // คืนใหม่ได้อีกหลังยกเลิก (จำนวนที่คืนได้กลับมา)
        $this->returnItem($sale, 4);
        $this->assertSame(2, SaleReturn::count());
    }

    public function test_void_return_is_blocked_when_stock_would_go_negative(): void
    {
        $p    = $this->product(['stock' => 10]);
        $sale = $this->cashSale($p, 3);
        $this->returnItem($sale, 2);

        $p->update(['stock' => 1]); // ของที่รับคืนถูกขายต่อไปแล้ว

        $return = SaleReturn::first();
        $this->delete(route('sale-returns.destroy', $return), ['void_reason' => 'x'])
            ->assertSessionHas('error');

        $this->assertSame('completed', $return->fresh()->status);
        $this->assertSame(1, $p->fresh()->stock);
    }

    public function test_cashier_cannot_void_a_return(): void
    {
        $p    = $this->product();
        $sale = $this->cashSale($p, 2);
        $this->returnItem($sale, 1);

        $this->actingAs($this->makeUser('cashier'))
            ->delete(route('sale-returns.destroy', SaleReturn::first()), ['void_reason' => 'x'])
            ->assertForbidden();

        $this->assertSame('completed', SaleReturn::first()->status);
    }

    public function test_return_form_redirects_when_sale_is_fully_returned(): void
    {
        $p    = $this->product();
        $sale = $this->cashSale($p, 2);
        $this->returnItem($sale, 2);

        $this->get(route('sale-returns.create', $sale))->assertRedirect(route('sales.show', $sale->id));
    }

    // ---------- G. แคชเชียร์รับเงินสดต้องมีกะเปิด ----------

    public function test_cashier_cash_receipt_requires_open_shift(): void
    {
        $c    = Customer::create(['name' => 'Cust', 'is_active' => true]);
        $p    = $this->product();
        $sale = $this->creditSale($p, $c, 1);

        config(['pos.require_open_shift' => true]);
        $this->actingAs($this->makeUser('cashier'));

        $this->post(route('receipts.store'), [
            'sale_id' => $sale->id, 'payment_date' => now()->toDateString(),
            'amount' => 100, 'payment_method' => 'cash',
        ])->assertSessionHasErrors('payment_method');

        $this->assertSame(0, \App\Models\Receipt::count());
    }

    // ---------- E/F. ลบผู้ใช้/ซัพพลายเออร์ที่มีประวัติ ----------

    public function test_cannot_delete_user_with_only_stock_document_history(): void
    {
        $u = $this->makeUser('stock');
        StockDocument::create([
            'doc_no' => 'IN-T-1', 'type' => 'in', 'date' => now()->toDateString(), 'user_id' => $u->id,
        ]);

        $this->delete(route('users.destroy', $u))->assertSessionHas('error');
        $this->assertNotNull(User::find($u->id));
    }

    public function test_cannot_delete_supplier_referenced_by_stock_document(): void
    {
        $s = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        StockDocument::create([
            'doc_no' => 'IN-T-2', 'type' => 'in', 'date' => now()->toDateString(),
            'user_id' => $this->admin->id, 'supplier_id' => $s->id,
        ]);

        $this->delete(route('suppliers.destroy', $s))->assertSessionHas('error');
        $this->assertNotNull(Supplier::find($s->id));
    }

    // ---------- H/K. ข้อมูลบริษัท ----------

    public function test_documents_show_company_name_from_settings(): void
    {
        Setting::set('company_name', 'ร้านทดสอบ 123');
        $p    = $this->product();
        $sale = $this->cashSale($p, 1);

        $this->get(route('sales.show', $sale->id))->assertOk()
            ->assertSee('ร้านทดสอบ 123')
            ->assertDontSee('AUTOPART');
    }

    public function test_company_settings_cannot_be_toggled_or_edited_as_custom(): void
    {
        Setting::set('company_name', 'ร้านเดิม');

        $this->patch(route('settings.toggle', 'company_name'))->assertSessionHas('error');
        $this->put(route('settings.update-custom', 'company_name'), ['value' => 'x'])->assertSessionHas('error');

        $s = Setting::where('key', 'company_name')->first();
        $this->assertTrue((bool) $s->is_active);
        $this->assertSame('ร้านเดิม', $s->value);
    }

    // ---------- I. เมนูใบสั่งซื้อสำหรับฝ่ายบัญชี ----------

    public function test_accountant_sees_purchase_order_menu(): void
    {
        $this->actingAs($this->makeUser('accountant'))
            ->get(route('dashboard'))->assertOk()
            ->assertSee(route('purchase-orders.index', [], false));
    }

    // ---------- J/L. ตรวจ input ----------

    public function test_stock_adjustment_quantity_has_upper_limit(): void
    {
        $p = $this->product();

        $this->post(route('stock-adjustments.store'), [
            'product_id' => $p->id, 'type' => 'addition', 'quantity' => 1000000, 'reason' => 'other',
        ])->assertSessionHasErrors('quantity');
    }

    public function test_incoming_stock_document_does_not_keep_customer(): void
    {
        $c = Customer::create(['name' => 'Cust', 'is_active' => true]);
        $p = $this->product();

        $this->post(route('stock-documents.store'), [
            'type' => 'in', 'date' => now()->toDateString(), 'customer_id' => $c->id,
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertNull(StockDocument::first()->customer_id);
    }
}
