<?php
namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression tests รอบที่ 5: ประวัติจ่ายเงิน PO (void ได้), คืนเงินสดโดยผู้ใช้ที่ไม่มีกะ,
 * idempotency key ข้ามผู้ใช้, กดบันทึกใบเบิก/รับสต็อกซ้ำ
 */
class RoundFiveFixesTest extends TestCase
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
            'stock' => 10, 'is_active' => true,
        ], $o));
    }

    /** PO ที่รับของแล้วผ่านระบบจริง (สร้างใบรับเข้าให้ด้วย) ยอดรวม 650 */
    private function receivedPoViaReceive(): array
    {
        $s  = Supplier::first() ?? Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $p  = $this->product(['stock' => 0, 'cost' => 100]);
        $po = PurchaseOrder::create([
            'po_no' => 'PO-' . uniqid(), 'supplier_id' => $s->id, 'user_id' => $this->admin->id,
            'order_date' => now()->toDateString(), 'status' => 'pending', 'total_amount' => 650,
        ]);
        $po->items()->create(['product_id' => $p->id, 'quantity' => 5, 'unit_cost' => 130, 'total' => 650]);
        $this->post(route('purchase-orders.receive', $po));

        return [$po->fresh(), $p->fresh(), StockDocument::first()];
    }

    private function cashSaleBy(User $cashier, Product $p, int $qty): Sale
    {
        $this->actingAs($cashier);
        $this->postJson(route('sales.store'), [
            'items'           => [['product_id' => $p->id, 'quantity' => $qty]],
            'payment_method'  => 'cash',
            'received_amount' => 100000,
        ])->assertOk();

        return Sale::latest('id')->first();
    }

    private function postCashReturn(Sale $sale, int $qty)
    {
        $item = $sale->items()->first();

        return $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => $qty, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'ทดสอบ',
        ]);
    }

    // ---------- 1. ประวัติจ่ายเงิน PO ----------

    public function test_pay_records_a_payment_row(): void
    {
        [$po] = $this->receivedPoViaReceive();

        $this->post(route('purchase-orders.pay', $po), ['amount' => 200])->assertSessionHas('success');

        $this->assertSame(1, PurchaseOrderPayment::where('purchase_order_id', $po->id)->count());
        $this->assertEquals(200, $po->fresh()->paid_amount);
        $this->get(route('purchase-orders.show', $po))->assertOk()->assertSee('ประวัติการจ่ายเงิน');
    }

    public function test_voiding_a_payment_restores_remaining_and_unlocks_receipt_void(): void
    {
        [$po, $p, $doc] = $this->receivedPoViaReceive();

        $this->post(route('purchase-orders.pay', $po), ['amount' => 100])->assertSessionHas('success');
        $pay = PurchaseOrderPayment::first();

        // จ่ายแล้ว → ห้ามยกเลิกใบรับเข้า
        $this->delete(route('stock-documents.destroy', $doc));
        $this->assertSame('received', $po->fresh()->status);

        // ยกเลิกรายการจ่าย → ยอดค้างกลับมา
        $this->post(route('purchase-orders.payments.void', [$po, $pay]))->assertSessionHas('success');
        $this->assertEquals(0, $po->fresh()->paid_amount);
        $this->assertEquals(650, $po->fresh()->remaining_amount);
        $this->assertSame('voided', $pay->fresh()->status);
        $this->assertNotNull($pay->fresh()->voided_at);
        $this->assertSame($this->admin->id, $pay->fresh()->voided_by);

        // ตอนนี้ยกเลิกใบรับเข้าได้
        $this->delete(route('stock-documents.destroy', $doc));
        $this->assertSame('pending', $po->fresh()->status);
        $this->assertSame(0, $p->fresh()->stock);
    }

    public function test_voiding_only_the_wrong_payment_keeps_the_other(): void
    {
        [$po] = $this->receivedPoViaReceive();

        $this->post(route('purchase-orders.pay', $po), ['amount' => 100]);
        $this->post(route('purchase-orders.pay', $po), ['amount' => 250]);
        $wrong = PurchaseOrderPayment::where('amount', 250)->first();

        $this->post(route('purchase-orders.payments.void', [$po, $wrong]))->assertSessionHas('success');

        $this->assertEquals(100, $po->fresh()->paid_amount);
        $this->assertEquals(550, $po->fresh()->remaining_amount);
    }

    public function test_cannot_void_the_same_payment_twice(): void
    {
        [$po] = $this->receivedPoViaReceive();
        $this->post(route('purchase-orders.pay', $po), ['amount' => 100]);
        $this->post(route('purchase-orders.pay', $po), ['amount' => 50]);
        $pay = PurchaseOrderPayment::where('amount', 100)->first();

        $this->post(route('purchase-orders.payments.void', [$po, $pay]))->assertSessionHas('success');
        $this->post(route('purchase-orders.payments.void', [$po, $pay]))->assertSessionHas('error');

        $this->assertEquals(50, $po->fresh()->paid_amount); // ไม่ถูกหักซ้ำ
    }

    public function test_cannot_void_a_payment_through_another_po(): void
    {
        [$po1] = $this->receivedPoViaReceive();
        $po2   = PurchaseOrder::create([
            'po_no' => 'PO-' . uniqid(), 'supplier_id' => $po1->supplier_id, 'user_id' => $this->admin->id,
            'order_date' => now()->toDateString(), 'status' => 'received', 'total_amount' => 500,
        ]);

        $this->post(route('purchase-orders.pay', $po1), ['amount' => 100]);
        $pay = PurchaseOrderPayment::first();

        $this->post(route('purchase-orders.payments.void', [$po2, $pay]))->assertNotFound();
        $this->assertEquals(100, $po1->fresh()->paid_amount);
        $this->assertSame('active', $pay->fresh()->status);
    }

    public function test_pay_with_same_idempotency_key_records_once(): void
    {
        [$po] = $this->receivedPoViaReceive();
        $key  = (string) Str::uuid();

        $this->post(route('purchase-orders.pay', $po), ['amount' => 100, 'idempotency_key' => $key])->assertSessionHas('success');
        $this->post(route('purchase-orders.pay', $po), ['amount' => 100, 'idempotency_key' => $key])->assertSessionHas('success');

        $this->assertSame(1, PurchaseOrderPayment::count());
        $this->assertEquals(100, $po->fresh()->paid_amount);
    }

    public function test_accountant_can_void_payment_but_cashier_cannot(): void
    {
        [$po] = $this->receivedPoViaReceive();
        $this->post(route('purchase-orders.pay', $po), ['amount' => 100]);
        $pay = PurchaseOrderPayment::first();

        $this->actingAs($this->makeUser('cashier'));
        $this->post(route('purchase-orders.payments.void', [$po, $pay]))->assertForbidden();
        $this->assertSame('active', $pay->fresh()->status);

        $this->actingAs($this->makeUser('accountant'));
        $this->post(route('purchase-orders.payments.void', [$po, $pay]))->assertSessionHas('success');
        $this->assertSame('voided', $pay->fresh()->status);
    }

    // ---------- 3. คืนเงินสดโดยผู้ใช้ที่ไม่มีกะของตัวเอง ----------

    public function test_admin_cash_refund_is_attributed_to_the_only_open_shift(): void
    {
        $cashier = $this->makeUser('cashier');
        $shift   = CashierShift::create([
            'user_id' => $cashier->id, 'opened_at' => now()->subHour(),
            'opening_cash' => 0, 'status' => 'open',
        ]);
        config(['pos.require_open_shift' => true]);

        $sale = $this->cashSaleBy($cashier, $this->product(), 2); // 400

        $this->actingAs($this->admin);
        $this->postCashReturn($sale, 1)->assertSessionHasNoErrors()->assertSessionMissing('error');

        $return = SaleReturn::first();
        $this->assertNotNull($return);
        $this->assertSame($shift->id, $return->cashier_shift_id);

        // ปิดกะโดยเจ้าของกะ → ยอดเงินสดคืนต้องถูกหักออกจากลิ้นชัก
        $this->actingAs($cashier);
        $this->post(route('cashier-shifts.close', $shift), ['closing_cash' => 200]);
        $this->assertEquals(200, $shift->fresh()->total_cash_refunds);
    }

    public function test_admin_cash_refund_without_any_open_shift_is_rejected(): void
    {
        $sale = $this->cashSaleBy($this->admin, $this->product(), 2); // ขายตอนปิดกฎกะ
        config(['pos.require_open_shift' => true]);

        $this->postCashReturn($sale, 1)->assertSessionHas('error');

        $this->assertSame(0, SaleReturn::count());
    }

    public function test_admin_cash_refund_with_multiple_open_shifts_is_rejected(): void
    {
        $sale = $this->cashSaleBy($this->admin, $this->product(), 2);
        foreach (['cashier', 'cashier'] as $role) {
            CashierShift::create([
                'user_id' => $this->makeUser($role)->id, 'opened_at' => now()->subHour(),
                'opening_cash' => 0, 'status' => 'open',
            ]);
        }
        config(['pos.require_open_shift' => true]);

        $this->actingAs($this->admin);
        $this->postCashReturn($sale, 1)->assertSessionHas('error');

        $this->assertSame(0, SaleReturn::count());
    }

    public function test_non_cash_refund_by_admin_does_not_need_a_shift(): void
    {
        $sale = $this->cashSaleBy($this->admin, $this->product(), 2);
        config(['pos.require_open_shift' => true]);

        $item = $sale->items()->first();
        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'transfer',
            'reason'        => 'ทดสอบ',
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertSame(1, SaleReturn::count());
    }

    // ---------- 4. idempotency key ข้ามผู้ใช้ ----------

    public function test_idempotency_key_clash_between_users_returns_422_not_500(): void
    {
        $p   = $this->product();
        $key = (string) Str::uuid();

        $this->postJson(route('sales.store'), [
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500, 'idempotency_key' => $key,
        ])->assertOk();

        $this->actingAs($this->makeUser('cashier'));
        $this->postJson(route('sales.store'), [
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500, 'idempotency_key' => $key,
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(1, Sale::count());
        $this->assertSame(9, $p->fresh()->stock);
    }

    public function test_same_user_replaying_idempotency_key_still_gets_original_sale(): void
    {
        $p   = $this->product();
        $key = (string) Str::uuid();
        $payload = [
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500, 'idempotency_key' => $key,
        ];

        $first  = $this->postJson(route('sales.store'), $payload)->assertOk()->json('sale_id');
        $second = $this->postJson(route('sales.store'), $payload)->assertOk()->json('sale_id');

        $this->assertSame($first, $second);
        $this->assertSame(1, Sale::count());
    }

    // ---------- 5. ใบเบิก/รับสต็อกกดซ้ำ ----------

    public function test_stock_document_with_same_idempotency_key_is_created_once(): void
    {
        $p   = $this->product(['stock' => 10]);
        $key = (string) Str::uuid();
        $payload = [
            'type' => 'out', 'date' => now()->toDateString(), 'idempotency_key' => $key,
            'items' => [['product_id' => $p->id, 'quantity' => 3]],
        ];

        $this->post(route('stock-documents.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('stock-documents.store'), $payload)->assertSessionHasNoErrors();

        $this->assertSame(1, StockDocument::count());
        $this->assertSame(7, $p->fresh()->stock); // ตัดสต็อกรอบเดียว
    }

    public function test_stock_document_without_key_still_works(): void
    {
        $p = $this->product(['stock' => 10]);

        $this->post(route('stock-documents.store'), [
            'type' => 'in', 'date' => now()->toDateString(),
            'items' => [['product_id' => $p->id, 'quantity' => 4]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(14, $p->fresh()->stock);
        $this->assertNull(StockDocument::first()->idempotency_key);
    }

    public function test_stock_document_create_form_renders_idempotency_key(): void
    {
        $this->get(route('stock-documents.create'))->assertOk()->assertSee('name="idempotency_key"', false);
    }
}
