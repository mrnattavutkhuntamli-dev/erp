<?php
namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\Quotation;
use App\Models\StockAdjustment;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression tests รอบที่ 6: idempotency (ปรับปรุงสต็อก/PO/ใบเสนอราคา/ค่าใช้จ่าย),
 * ประวัติยกเลิกใบรับ-เบิก, ข้อมูลการจ่ายเงิน PO, รีเซ็ต 2FA โดย admin, ข้อความภาษาไทย
 */
class RoundSixFixesTest extends TestCase
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

    // ---------- idempotency ----------

    public function test_stock_adjustment_with_same_key_applies_once(): void
    {
        $p = $this->product(['stock' => 10]);
        $payload = [
            'product_id' => $p->id, 'type' => 'subtraction', 'quantity' => 3, 'reason' => 'damaged',
            'idempotency_key' => 'adj-key-1',
        ];

        $this->post(route('stock-adjustments.store'), $payload);
        $this->post(route('stock-adjustments.store'), $payload);

        $this->assertSame(1, StockAdjustment::count());
        $this->assertSame(7, $p->fresh()->stock);
    }

    public function test_stock_adjustment_without_key_still_works(): void
    {
        $p = $this->product(['stock' => 10]);

        $this->post(route('stock-adjustments.store'), [
            'product_id' => $p->id, 'type' => 'addition', 'quantity' => 2, 'reason' => 'stocktake',
        ])->assertSessionHasNoErrors();

        $this->assertSame(12, $p->fresh()->stock);
    }

    public function test_purchase_order_with_same_key_is_created_once(): void
    {
        $s = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $p = $this->product();
        $payload = [
            'supplier_id' => $s->id, 'order_date' => now()->toDateString(),
            'items' => [['product_id' => $p->id, 'quantity' => 2, 'unit_cost' => 50]],
            'idempotency_key' => 'po-key-1',
        ];

        $this->post(route('purchase-orders.store'), $payload);
        $this->post(route('purchase-orders.store'), $payload);

        $this->assertSame(1, PurchaseOrder::count());
    }

    public function test_quotation_with_same_key_is_created_once(): void
    {
        $p = $this->product();
        $payload = [
            'quote_date' => now()->toDateString(), 'valid_until' => now()->addDays(5)->toDateString(),
            'items' => [['product_id' => $p->id, 'quantity' => 1, 'price' => 200]],
            'idempotency_key' => 'qt-key-1',
        ];

        $this->post(route('quotations.store'), $payload);
        $this->post(route('quotations.store'), $payload);

        $this->assertSame(1, Quotation::count());
    }

    public function test_expense_with_same_key_is_created_once(): void
    {
        $payload = [
            'expense_date' => now()->toDateString(), 'category' => 'other',
            'description' => 'ค่าน้ำแข็ง', 'amount' => 120, 'idempotency_key' => 'ex-key-1',
        ];

        $this->post(route('expenses.store'), $payload);
        $this->post(route('expenses.store'), $payload);

        $this->assertSame(1, Expense::count());
    }

    // ---------- จ่ายเงิน PO: วันที่/วิธีจ่าย/หมายเหตุ ----------

    public function test_po_payment_records_date_method_and_note(): void
    {
        $s  = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $p  = $this->product(['stock' => 0]);
        $po = PurchaseOrder::create([
            'po_no' => 'PO-' . uniqid(), 'supplier_id' => $s->id, 'user_id' => $this->admin->id,
            'order_date' => now()->toDateString(), 'status' => 'pending', 'total_amount' => 500,
        ]);
        $po->items()->create(['product_id' => $p->id, 'quantity' => 5, 'unit_cost' => 100, 'total' => 500]);
        $this->post(route('purchase-orders.receive', $po));

        $date = now()->subDay()->toDateString();
        $this->post(route('purchase-orders.pay', $po), [
            'amount' => 200, 'payment_date' => $date, 'payment_method' => 'transfer', 'note' => 'โอน SCB',
        ])->assertSessionHasNoErrors();

        $pay = PurchaseOrderPayment::first();
        $this->assertSame('transfer', $pay->payment_method);
        $this->assertSame('โอน SCB', $pay->note);
        $this->assertSame($date, $pay->payment_date->toDateString());
    }

    public function test_po_payment_rejects_future_date_and_unknown_method(): void
    {
        $s  = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $po = PurchaseOrder::create([
            'po_no' => 'PO-' . uniqid(), 'supplier_id' => $s->id, 'user_id' => $this->admin->id,
            'order_date' => now()->toDateString(), 'status' => 'received', 'total_amount' => 500,
        ]);

        $this->post(route('purchase-orders.pay', $po), [
            'amount' => 100, 'payment_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('payment_date');

        $this->post(route('purchase-orders.pay', $po), [
            'amount' => 100, 'payment_method' => 'bitcoin',
        ])->assertSessionHasErrors('payment_method');
    }

    // ---------- void ใบรับ/ใบเบิก เก็บประวัติ ----------

    public function test_voiding_stock_document_records_who_when_and_why(): void
    {
        $p = $this->product(['stock' => 10]);
        $this->post(route('stock-documents.store'), [
            'type' => 'out', 'date' => now()->toDateString(), 'receiver_name' => 'ช่าง',
            'items' => [['product_id' => $p->id, 'quantity' => 4]],
        ])->assertSessionHasNoErrors();
        $doc = StockDocument::first();

        $this->delete(route('stock-documents.destroy', $doc), ['void_reason' => 'เบิกผิดรายการ']);

        $doc = $doc->fresh();
        $this->assertSame('voided', $doc->status);
        $this->assertSame('เบิกผิดรายการ', $doc->void_reason);
        $this->assertSame($this->admin->id, $doc->voided_by);
        $this->assertNotNull($doc->voided_at);
        $this->assertSame(10, $p->fresh()->stock);

        $this->get(route('stock-documents.show', $doc))
            ->assertOk()
            ->assertSee('เบิกผิดรายการ');
    }

    public function test_voiding_stock_document_without_reason_still_works(): void
    {
        $p = $this->product(['stock' => 10]);
        $this->post(route('stock-documents.store'), [
            'type' => 'out', 'date' => now()->toDateString(),
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ]);
        $doc = StockDocument::first();

        $this->delete(route('stock-documents.destroy', $doc))->assertSessionHasNoErrors();

        $this->assertSame('voided', $doc->fresh()->status);
        $this->assertNull($doc->fresh()->void_reason);
    }

    // ---------- 2FA ----------

    public function test_admin_can_reset_another_users_2fa(): void
    {
        $cashier = $this->makeUser('cashier');
        DB::table('users')->where('id', $cashier->id)->update([
            'google2fa_enabled' => true,
            'google2fa_secret'  => 'garbage-that-cannot-be-decrypted',
        ]);

        $this->post(route('users.reset-2fa', $cashier))->assertSessionHas('success');

        $fresh = DB::table('users')->where('id', $cashier->id)->first();
        $this->assertEquals(0, $fresh->google2fa_enabled);
        $this->assertNull($fresh->google2fa_secret);
    }

    public function test_cashier_cannot_reset_2fa(): void
    {
        $other   = $this->makeUser('stock');
        $cashier = $this->makeUser('cashier');
        $this->actingAs($cashier);

        $this->post(route('users.reset-2fa', $other))->assertForbidden();
    }

    public function test_enabling_2fa_with_unreadable_secret_does_not_500(): void
    {
        $u = $this->makeUser('cashier');
        DB::table('users')->where('id', $u->id)->update([
            'google2fa_enabled' => false,
            'google2fa_secret'  => 'garbage-that-cannot-be-decrypted',
        ]);
        $this->actingAs($u->fresh());

        // หน้าตั้งค่าสร้าง secret ใหม่ให้ได้ ไม่ error
        $this->get(route('two-factor.show'))->assertOk();
    }

    public function test_enable_without_any_secret_redirects_back_to_setup(): void
    {
        $u = $this->makeUser('cashier');
        $this->actingAs($u);

        $this->post(route('two-factor.enable'), ['otp' => '123456'])
            ->assertRedirect(route('two-factor.show'));
    }

    // ---------- ภาษาไทย / config ----------

    public function test_validation_messages_are_thai(): void
    {
        $this->post(route('stock-adjustments.store'), [])
            ->assertSessionHasErrors('product_id');

        $msg = session('errors')->first('product_id');
        $this->assertStringContainsString('กรุณากรอก', $msg);
    }

    public function test_low_stock_threshold_is_configurable(): void
    {
        $this->product(['name' => 'ผ้าเบรกเหลือยี่สิบ', 'stock' => 20]);

        config(['pos.low_stock_threshold' => 10]);
        $this->assertCount(0, $this->get(route('dashboard'))->viewData('lowStockProducts'));

        config(['pos.low_stock_threshold' => 30]);
        $this->assertCount(1, $this->get(route('dashboard'))->viewData('lowStockProducts'));
    }
}
