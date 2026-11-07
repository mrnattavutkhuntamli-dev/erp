<?php
namespace Tests\Feature;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\ShiftSchedule;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression tests: ยืนยันว่าบัคที่เคยพบถูกแก้แล้ว และกันไม่ให้กลับมาอีก
 */
class BusinessRulesTest extends TestCase
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------- helpers ----------

    private function makeUser(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);
        return $u->fresh();
    }

    private function openShiftFor(User $u, array $o = []): CashierShift
    {
        return CashierShift::create(array_merge([
            'user_id' => $u->id, 'opened_at' => now()->subHour(), 'opening_cash' => 0, 'status' => 'open',
        ], $o));
    }

    private function product(array $o = []): Product
    {
        return Product::create(array_merge([
            'sku'   => 'T-' . uniqid(), 'name' => 'Brake Pad', 'cost' => 100, 'price' => 200,
            'stock' => 10, 'is_active'         => true,
        ], $o));
    }

    private function quotation(Product $p, array $o = [], float $price = 200): Quotation
    {
        $q = Quotation::create(array_merge([
            'quote_no'    => 'QT-' . uniqid(), 'user_id' => $this->admin->id,
            'quote_date'  => now()->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
            'subtotal'    => $price, 'discount'          => 0, 'total' => $price, 'status' => 'sent',
        ], $o));
        $q->items()->create(['product_id' => $p->id, 'quantity' => 1, 'price' => $price, 'total' => $price]);
        return $q;
    }

    private function receivedPo(float $total = 1000, ?string $orderDate = null): PurchaseOrder
    {
        $s = Supplier::first() ?? Supplier::create(['name' => 'Sup', 'is_active' => true]);
        return PurchaseOrder::create([
            'po_no'      => 'PO-' . uniqid(), 'supplier_id'               => $s->id, 'user_id'          => $this->admin->id,
            'order_date' => $orderDate ?? now()->toDateString(), 'status' => 'received', 'total_amount' => $total,
        ]);
    }

    // ---------- รับคืนสินค้า ----------

    private function makeSale(Product $p, int $qty, array $extra = []): Sale
    {
        $this->postJson(route('sales.store'), array_merge([
            'items'          => [['product_id' => $p->id, 'quantity' => $qty]],
            'payment_method' => 'cash', 'received_amount' => 100000,
        ], $extra))->assertOk();

        return Sale::latest('id')->first();
    }

    // ---------- #1 สต็อก ----------

    public function test_product_edit_form_without_stock_field_saves(): void
    {
        $p = $this->product(['stock' => 5]);
        $this->put(route('products.update', $p), ['name' => 'New', 'sku' => $p->sku, 'cost' => 100, 'price' => 250])
            ->assertSessionHasNoErrors();
        $this->assertSame('New', $p->fresh()->name);
        $this->assertSame(5, $p->fresh()->stock);
    }

    public function test_product_update_never_overwrites_stock(): void
    {
        $p = $this->product(['stock' => 7]);
        $this->put(route('products.update', $p), [
            'name' => $p->name, 'sku' => $p->sku, 'cost' => 100, 'price' => 250, 'stock' => 10, // ฟอร์มเก่าที่ค้างอยู่
        ]);
        $this->assertSame(7, $p->fresh()->stock);
    }

    public function test_creating_product_with_stock_records_opening_stock_document(): void
    {
        $this->post(route('products.store'), ['name' => 'X', 'cost' => 10, 'price' => 20, 'stock' => 12]);
        $p = Product::firstWhere('name', 'X');
        $this->assertNotNull($p);
        $doc = StockDocument::where('doc_no', 'IN-OPEN-' . $p->sku)->first();
        $this->assertNotNull($doc);
        $this->assertSame(12, (int) $doc->items()->first()->quantity);
    }

    // ---------- #2 ลบสินค้าที่อยู่ในใบเสนอราคา ----------

    public function test_cannot_delete_product_used_in_quotation(): void
    {
        $p = $this->product();
        $q = $this->quotation($p);
        $this->delete(route('products.destroy', $p))->assertSessionHas('error');
        $this->assertNotNull(Product::find($p->id));
        $this->assertSame(1, $q->items()->count());
    }

    public function test_quotation_item_fk_is_restrict_at_database_level(): void
    {
        $p = $this->product();
        $this->quotation($p);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('products')->where('id', $p->id)->delete();
    }

    // ---------- #3 ใบเสนอราคาหมดอายุ / สินค้าปิดการขาย ----------

    public function test_expired_quotation_cannot_be_converted_and_is_marked_expired(): void
    {
        $q = $this->quotation($this->product(), [
            'quote_date'  => now()->subDays(60)->toDateString(),
            'valid_until' => now()->subDays(30)->toDateString(),
        ]);
        $this->post(route('quotations.convert', $q), ['payment_method' => 'cash', 'received_amount' => 200])
            ->assertSessionHas('error');
        $this->assertSame('expired', $q->fresh()->status);
        $this->assertSame(0, Sale::count());
    }

    public function test_quotation_with_inactive_product_cannot_be_converted(): void
    {
        $q = $this->quotation($this->product(['is_active' => false]));
        $this->post(route('quotations.convert', $q), ['payment_method' => 'cash', 'received_amount' => 200])
            ->assertSessionHas('error');
        $this->assertSame(0, Sale::count());
        $this->assertSame('sent', $q->fresh()->status);
    }

    public function test_valid_quotation_still_converts(): void
    {
        $p = $this->product(['stock' => 3]);
        $q = $this->quotation($p);
        $this->post(route('quotations.convert', $q), ['payment_method' => 'cash', 'received_amount' => 200]);
        $this->assertSame('accepted', $q->fresh()->status);
        $this->assertSame(2, $p->fresh()->stock);
    }

    public function test_quotations_index_marks_past_validity_as_expired(): void
    {
        $q = $this->quotation($this->product(), ['valid_until' => now()->subDay()->toDateString()]);
        $this->get(route('quotations.index'))->assertOk();
        $this->assertSame('expired', $q->fresh()->status);
    }

    // ---------- #12 ราคา/ส่วนลดในใบเสนอราคา ----------

    public function test_non_admin_cannot_quote_below_cost_or_over_discount_cap(): void
    {
        $cashier = $this->makeUser('cashier');
        $this->actingAs($cashier);
        $p    = $this->product(['cost' => 100]);
        $base = [
            'quote_date' => now()->toDateString(), 'valid_until' => now()->addDays(5)->toDateString(),
            'items'      => [['product_id' => $p->id, 'quantity' => 1, 'price' => 150]],
        ];

        // ราคาต่ำกว่าทุน
        $this->post(route('quotations.store'), array_replace_recursive($base, ['items' => [['price' => 50]]]))
            ->assertSessionHas('error');
        // ส่วนลด 20% > เพดาน 10%
        $this->post(route('quotations.store'), $base + ['discount' => 30])->assertSessionHas('error');
        $this->assertSame(0, Quotation::count());

        // ภายในเกณฑ์: ผ่าน
        $this->post(route('quotations.store'), $base + ['discount' => 15]);
        $this->assertSame(1, Quotation::count());
    }

    public function test_admin_can_quote_below_cost(): void
    {
        $p = $this->product(['cost' => 100]);
        $this->post(route('quotations.store'), [
            'quote_date' => now()->toDateString(), 'valid_until' => now()->addDays(5)->toDateString(),
            'items'      => [['product_id' => $p->id, 'quantity' => 1, 'price' => 50]],
        ]);
        $this->assertSame(1, Quotation::count());
    }

    public function test_quotation_store_works_without_customer_field(): void
    {
        $p = $this->product();
        $this->post(route('quotations.store'), [
            'quote_date' => now()->toDateString(), 'valid_until' => now()->addDays(5)->toDateString(),
            'items'      => [['product_id' => $p->id, 'quantity' => 1, 'price' => 200]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, Quotation::count());
        $this->assertNull(Quotation::first()->customer_id);
    }

    // ---------- #4 ปิดกะ + ค่าใช้จ่าย ----------

    public function test_shift_close_deducts_only_cash_drawer_expenses(): void
    {
        $shift = CashierShift::create([
            'user_id'      => $this->admin->id, 'opened_at' => now()->subHour(),
            'opening_cash' => 1000, 'status'                => 'open',
        ]);
        Expense::create(['expense_date' => now()->toDateString(), 'category' => 'supplies',
            'description'                   => 'จากลิ้นชัก', 'amount'            => 500, 'from_cash_drawer' => true, 'user_id' => $this->admin->id]);
        Expense::create(['expense_date' => now()->toDateString(), 'category' => 'rent',
            'description'                   => 'โอนธนาคาร', 'amount'             => 9000, 'from_cash_drawer' => false, 'user_id' => $this->admin->id]);

        $this->post(route('cashier-shifts.close', $shift), ['closing_cash' => 500]);

        $shift = $shift->fresh();
        $this->assertEquals(500, $shift->total_expenses);
        $this->get(route('cashier-shifts.show', $shift))->assertOk()
            ->assertViewHas('expectedCash', 500.0)
            ->assertViewHas('difference', 0.0);
    }

    public function test_expense_form_saves_cash_drawer_flag(): void
    {
        $this->openShiftFor($this->admin); // จ่ายจากลิ้นชักต้องมีกะที่เปิดอยู่ให้หัก
        $this->post(route('expenses.store'), [
            'expense_date' => now()->toDateString(), 'category' => 'other',
            'description'  => 'x', 'amount'                     => 100, 'from_cash_drawer' => '1',
        ]);
        $this->assertTrue((bool) Expense::first()->from_cash_drawer);

        $this->post(route('expenses.store'), [
            'expense_date' => now()->toDateString(), 'category' => 'other', 'description' => 'y', 'amount' => 100,
        ]);
        $this->assertFalse((bool) Expense::latest('id')->first()->from_cash_drawer);
    }

    // ---------- #5 เจ้าหนี้ (A/P) ----------

    public function test_ap_aging_counts_only_received_unpaid_pos_by_remaining_amount(): void
    {
        $pending = $this->receivedPo(500);
        $pending->update(['status' => 'pending']);
        $cancel = $this->receivedPo(500);
        $cancel->update(['status' => 'cancelled']);
        $paid = $this->receivedPo(700);
        $paid->update(['paid_amount' => 700]);
        $partial = $this->receivedPo(1000);
        $partial->update(['paid_amount' => 400]);
        $open = $this->receivedPo(250);

        $r      = $this->get(route('reports.ap-aging'))->assertOk();
        $totals = $r->viewData('totals');
        $this->assertEquals(600 + 250, $totals['total']);
        $r->assertSee($partial->po_no)->assertSee($open->po_no)
            ->assertDontSee($pending->po_no)->assertDontSee($cancel->po_no)->assertDontSee($paid->po_no);
    }

    public function test_paying_a_po_reduces_payables_and_rejects_overpayment(): void
    {
        $po = $this->receivedPo(1000);

        $this->post(route('purchase-orders.pay', $po), ['amount' => 1500])->assertSessionHas('error');
        $this->assertEquals(0, $po->fresh()->paid_amount);

        $this->post(route('purchase-orders.pay', $po), ['amount' => 400])->assertSessionHas('success');
        $this->assertEquals(600, $po->fresh()->remaining_amount);

        $this->post(route('purchase-orders.pay', $po), ['amount' => 600]);
        $this->assertEquals(0, $po->fresh()->remaining_amount);
        $this->post(route('purchase-orders.pay', $po), ['amount' => 1])->assertSessionHas('error');

        $this->assertEquals(0, $this->get(route('reports.ap-aging'))->viewData('totals')['total']);
    }

    public function test_cannot_pay_a_po_that_is_not_received(): void
    {
        $po = $this->receivedPo(1000);
        $po->update(['status' => 'pending']);
        $this->post(route('purchase-orders.pay', $po), ['amount' => 100])->assertSessionHas('error');
    }

    public function test_po_permissions_accountant_can_view_and_pay_but_not_receive(): void
    {
        $po = $this->receivedPo(1000);
        $this->actingAs($this->makeUser('accountant'));
        $this->get(route('purchase-orders.show', $po))->assertOk();
        $this->post(route('purchase-orders.pay', $po), ['amount' => 100])->assertSessionHas('success');
        $this->post(route('purchase-orders.receive', $po))->assertForbidden();

        $this->actingAs($this->makeUser('stock'));
        $this->get(route('purchase-orders.show', $po))->assertOk();
        $this->post(route('purchase-orders.pay', $po), ['amount' => 100])->assertForbidden();
    }

    // ---------- #6 ลูกหนี้ (A/R) / due_date ----------

    public function test_credit_sale_sets_due_date_and_ar_aging_counts_from_due_date(): void
    {
        config(['pos.credit_days' => 30]);
        $c = Customer::create(['name' => 'Cust', 'is_active' => true]);
        $p = $this->product();
        $this->postJson(route('sales.store'), [
            'customer_id'    => $c->id, 'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'credit',
        ])->assertOk();

        $sale = Sale::first();
        $this->assertSame(now()->addDays(30)->toDateString(), $sale->due_date->toDateString());

        // ขายเมื่อ 45 วันก่อน, ครบกำหนดเมื่อ 15 วันก่อน → เกินกำหนด 15 วัน
        $sale->forceFill(['created_at' => now()->subDays(45), 'due_date' => now()->subDays(15)->toDateString()])->save();
        $row = $this->get(route('reports.ar-aging'))->assertOk()->viewData('agingData')[$c->id]['invoices'][0];
        $this->assertSame(15, $row['days']);

        // ยังไม่ครบกำหนด → 0 วัน
        $sale->forceFill(['due_date' => now()->addDays(5)->toDateString()])->save();
        $row = $this->get(route('reports.ar-aging'))->viewData('agingData')[$c->id]['invoices'][0];
        $this->assertSame(0, $row['days']);
    }

    public function test_cash_sale_has_no_due_date(): void
    {
        $p = $this->product();
        $this->postJson(route('sales.store'), [
            'items'          => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500,
        ])->assertOk();
        $this->assertNull(Sale::first()->due_date);
    }

    public function test_quotation_credit_conversion_sets_due_date(): void
    {
        $c = Customer::create(['name' => 'Cust', 'is_active' => true]);
        $q = $this->quotation($this->product(), ['customer_id' => $c->id]);
        $this->post(route('quotations.convert', $q), ['payment_method' => 'credit']);
        $this->assertNotNull(Sale::first()->due_date);
    }

    // ---------- #7, #8 ผู้ใช้ ----------

    public function test_rejected_last_admin_update_saves_nothing(): void
    {
        $oldName  = $this->admin->name;
        $oldEmail = $this->admin->email;
        $this->put(route('users.update', $this->admin), [
            'name'     => 'HACKED', 'email'                         => 'new@example.com',
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123', 'role' => 'cashier',
        ])->assertSessionHas('error');

        $fresh = $this->admin->fresh();
        $this->assertTrue($fresh->hasRole('admin'));
        $this->assertSame($oldName, $fresh->name);
        $this->assertSame($oldEmail, $fresh->email);
        $this->assertFalse(Hash::check('newpassword123', $fresh->password));
    }

    public function test_user_update_still_works_normally(): void
    {
        $u = $this->makeUser('cashier');
        $this->put(route('users.update', $u), ['name' => 'Renamed', 'email' => $u->email, 'role' => 'stock']);
        $this->assertSame('Renamed', $u->fresh()->name);
        $this->assertTrue($u->fresh()->hasRole('stock'));
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $u = User::factory()->create(['password' => Hash::make('secret-pass-123')]);
        $u->assignRole('cashier');
        $this->patch(route('users.toggle-active', $u))->assertSessionHas('success');
        $this->assertFalse($u->fresh()->is_active);

        auth()->logout();
        $this->post('/login', ['email' => $u->email, 'password' => 'secret-pass-123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // เปิดใช้งานอีกครั้งแล้วเข้าได้
        $this->actingAs($this->admin)->patch(route('users.toggle-active', $u));
        auth()->logout();
        $this->post('/login', ['email' => $u->email, 'password' => 'secret-pass-123']);
        $this->assertAuthenticatedAs($u->fresh());
    }

    public function test_existing_session_of_deactivated_user_is_kicked_out(): void
    {
        $u = $this->makeUser('cashier');
        $this->actingAs($u)->get(route('dashboard'))->assertOk();

        $u->forceFill(['is_active' => false])->save();
        $this->actingAs($u->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_cannot_deactivate_self_or_last_active_admin(): void
    {
        $this->patch(route('users.toggle-active', $this->admin))->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);

        $other = $this->makeUser('admin');
        $this->actingAs($other);
        $this->patch(route('users.toggle-active', $this->admin))->assertSessionHas('success'); // ยังเหลือ admin อีกคน
        $this->actingAs($this->makeUser('admin'));
        $this->assertFalse($this->admin->fresh()->is_active);
    }

    public function test_deactivated_users_are_not_listed_for_new_shift_schedules(): void
    {
        $active = $this->makeUser('cashier');
        $gone   = $this->makeUser('cashier');
        $gone->forceFill(['is_active' => false])->save();

        $r = $this->post(route('shift-schedules.store'), [
            'user_id' => $gone->id, 'shift_date' => now()->toDateString(), 'start_time' => '08:00', 'end_time' => '17:00',
        ]);
        $r->assertSessionHasErrors('user_id');

        $this->post(route('shift-schedules.store'), [
            'user_id' => $active->id, 'shift_date' => now()->toDateString(), 'start_time' => '08:00', 'end_time' => '17:00',
        ])->assertSessionHasNoErrors();
    }

    // ---------- #9 slug หมวดหมู่ ----------

    public function test_thai_named_categories_get_unique_slugs_even_in_large_numbers(): void
    {
        for ($i = 0; $i < 80; $i++) { // เดิม: slug "-NNN" 900 ค่า ชนง่ายเมื่อชื่อไทยเยอะ
            $this->post(route('categories.store'), ['name' => "หมวดหมู่ที่ $i"])->assertSessionHasNoErrors();
        }
        $this->assertSame(80, Category::count());
        $this->assertSame(80, Category::distinct('slug')->count('slug'));
        $this->assertStringStartsWith('category-', Category::first()->slug);
    }

    public function test_english_category_slug_keeps_readable_base_and_rename_updates_it(): void
    {
        $this->post(route('categories.store'), ['name' => 'Brake Pad']);
        $c = Category::first();
        $this->assertStringStartsWith('brake-pad-', $c->slug);

        $this->put(route('categories.update', $c), ['name' => 'Oil Filter']);
        $this->assertStringStartsWith('oil-filter-', $c->fresh()->slug);
    }

    // ---------- #10 void ใบรับเข้าจาก PO ----------

    public function test_voiding_po_stock_document_reverts_po_and_allows_receiving_again(): void
    {
        $s  = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $p  = $this->product(['stock' => 0]);
        $po = PurchaseOrder::create([
            'po_no'      => 'PO-X', 'supplier_id'           => $s->id, 'user_id'         => $this->admin->id,
            'order_date' => now()->toDateString(), 'status' => 'pending', 'total_amount' => 500,
        ]);
        $po->items()->create(['product_id' => $p->id, 'quantity' => 5, 'unit_cost' => 100, 'total' => 500]);

        $this->post(route('purchase-orders.receive', $po));
        $this->assertSame(5, $p->fresh()->stock);
        $doc = StockDocument::first();
        $this->assertSame($po->id, $doc->purchase_order_id);

        $this->delete(route('stock-documents.destroy', $doc));
        $this->assertSame(0, $p->fresh()->stock);
        $this->assertSame('pending', $po->fresh()->status);

        $this->post(route('purchase-orders.receive', $po))->assertSessionHasNoErrors();
        $this->assertSame(5, $p->fresh()->stock);
        $this->assertSame('received', $po->fresh()->status);
        $this->assertSame(2, StockDocument::count());
        $this->assertSame(2, StockDocument::distinct('doc_no')->count('doc_no'));
    }

    public function test_voiding_legacy_po_document_without_link_matches_by_doc_no(): void
    {
        $p   = $this->product(['stock' => 5]);
        $po  = $this->receivedPo(500);
        $doc = StockDocument::create([
            'doc_no'  => 'IN-PO-' . $po->po_no, 'type' => 'in', 'date' => now()->toDateString(),
            'user_id' => $this->admin->id, 'status'    => 'completed',
        ]);
        $doc->items()->create(['product_id' => $p->id, 'quantity' => 5, 'stock_after' => 5]);

        $this->delete(route('stock-documents.destroy', $doc));
        $this->assertSame('pending', $po->fresh()->status);
    }

    // ---------- #11 idempotency / ลูกค้า (#14) ----------

    public function test_idempotency_key_prevents_duplicate_sales(): void
    {
        $p       = $this->product(['stock' => 10]);
        $payload = [
            'items'          => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500, 'idempotency_key' => (string) Str::uuid(),
        ];
        $this->postJson(route('sales.store'), $payload)->assertOk();
        $this->postJson(route('sales.store'), $payload)->assertOk();
        $this->assertSame(1, Sale::count());
        $this->assertSame(9, $p->fresh()->stock);
    }

    public function test_unique_violation_on_idempotency_key_returns_existing_sale(): void
    {
        // จำลอง race: มีบิลที่ใช้ key นี้อยู่แล้ว แต่การเช็กเบื้องต้นใน transaction พลาด (ชนที่ unique index จริง)
        $p     = $this->product(['stock' => 10]);
        $key   = (string) Str::uuid();
        $first = $this->postJson(route('sales.store'), [
            'items'          => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500, 'idempotency_key' => $key,
        ])->assertOk()->json('sale_id');

        Sale::where('id', $first)->update(['idempotency_key' => $key]); // key ยังอยู่ — ยืนยันว่า unique index มีจริง
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Sale::create([
            'invoice_no'      => 'DUP-' . uniqid(), 'user_id' => $this->admin->id, 'subtotal' => 1, 'discount'                  => 0,
            'total'           => 1, 'payment_method'          => 'cash', 'payment_status'     => 'paid', 'paid_amount'          => 1,
            'received_amount' => 1, 'change_amount'           => 0, 'status'                  => 'completed', 'idempotency_key' => $key,
        ]);
    }

    public function test_inactive_customer_cannot_be_used_in_pos_or_quotation(): void
    {
        $c = Customer::create(['name' => 'Gone', 'is_active' => false]);
        $p = $this->product();

        $this->postJson(route('sales.store'), [
            'customer_id'    => $c->id, 'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'credit',
        ])->assertStatus(422);

        $this->post(route('quotations.store'), [
            'customer_id' => $c->id, 'quote_date' => now()->toDateString(),
            'valid_until' => now()->addDays(3)->toDateString(),
            'items'       => [['product_id' => $p->id, 'quantity' => 1, 'price' => 200]],
        ])->assertSessionHasErrors('customer_id');
    }

    // ---------- #13 ยี่ห้อ/รุ่นรถ ----------

    public function test_cannot_delete_car_brand_or_model_linked_to_products(): void
    {
        $b = CarBrand::create(['name' => 'Toyota']);
        $m = CarModel::create(['car_brand_id' => $b->id, 'name' => 'Vios']);
        $p = $this->product();
        $p->carModels()->sync([$m->id]);

        $this->delete(route('car-brands.destroy', $b))->assertSessionHas('error');
        $this->assertNotNull(CarBrand::find($b->id));

        $this->delete(route('car-models.destroy', $m->id))->assertSessionHas('error');
        $this->assertNotNull(CarModel::find($m->id));
        $this->assertSame(1, $p->carModels()->count());

        // เอาออกจากสินค้าแล้วลบได้
        $p->carModels()->sync([]);
        $this->delete(route('car-brands.destroy', $b))->assertSessionHas('success');
        $this->assertNull(CarBrand::find($b->id));
    }

    // ---------- #16 กะข้ามคืน ----------

    public function test_overnight_schedule_can_be_created_and_overlaps_are_detected(): void
    {
        $u    = $this->makeUser('cashier');
        $date = now()->toDateString();

        $this->post(route('shift-schedules.store'), [
            'user_id' => $u->id, 'shift_date' => $date, 'start_time' => '22:00', 'end_time' => '06:00',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, ShiftSchedule::count());

        // ทับกะข้ามคืน (วันถัดไป 01:00-03:00)
        $this->post(route('shift-schedules.store'), [
            'user_id' => $u->id, 'shift_date' => now()->addDay()->toDateString(), 'start_time' => '01:00', 'end_time' => '03:00',
        ])->assertSessionHasErrors('shift_date');

        // ไม่ทับ (วันถัดไป 08:00-17:00)
        $this->post(route('shift-schedules.store'), [
            'user_id' => $u->id, 'shift_date' => now()->addDay()->toDateString(), 'start_time' => '08:00', 'end_time' => '17:00',
        ])->assertSessionHasNoErrors();

        // เริ่ม = จบ ไม่อนุญาต
        $this->post(route('shift-schedules.store'), [
            'user_id' => $u->id, 'shift_date' => now()->addDays(5)->toDateString(), 'start_time' => '08:00', 'end_time' => '08:00',
        ])->assertSessionHasErrors('end_time');
    }

    public function test_overnight_shift_can_open_before_and_after_midnight_and_blocks_after_end(): void
    {
        config(['pos.require_open_shift' => true]);
        $u = $this->makeUser('cashier');
        $this->actingAs($u);
        $day = Carbon::parse('2026-11-10');
        ShiftSchedule::create(['user_id' => $u->id, 'shift_date' => $day->toDateString(), 'start_time' => '22:00:00', 'end_time' => '06:00:00']);

        // เปิดกะเวลา 23:00
        Carbon::setTestNow($day->copy()->setTime(23, 0));
        $this->post(route('cashier-shifts.store'), ['opening_cash' => 100])->assertSessionHas('success');
        $shift = CashierShift::first();
        $this->assertNull(CashierShift::sellBlockReason($u));

        // ตี 2 ของวันถัดไป ยังขายได้ (เดิม: ถือว่าหมดเวลากะตั้งแต่เปิดกะ)
        Carbon::setTestNow($day->copy()->addDay()->setTime(2, 0));
        $this->assertNull(CashierShift::sellBlockReason($u));

        // 06:30 เลยเวลากะ → ถูกบล็อก
        Carbon::setTestNow($day->copy()->addDay()->setTime(6, 30));
        $this->assertNotNull(CashierShift::sellBlockReason($u));

        // ผู้ที่เปิดกะหลังเที่ยงคืน (ตี 1) ของกะที่เริ่มเมื่อวาน ก็เปิดได้และจบ 06:00 วันเดียวกัน
        $shift->update(['status' => 'closed']);
        Carbon::setTestNow($day->copy()->addDay()->setTime(1, 0));
        $this->post(route('cashier-shifts.store'), ['opening_cash' => 100])->assertSessionHas('success');
        Carbon::setTestNow($day->copy()->addDay()->setTime(5, 0));
        $this->assertNull(CashierShift::sellBlockReason($u));
        Carbon::setTestNow($day->copy()->addDay()->setTime(7, 0));
        $this->assertNotNull(CashierShift::sellBlockReason($u));
    }

    public function test_regular_day_shift_behaviour_is_unchanged(): void
    {
        config(['pos.require_open_shift' => true]);
        $u = $this->makeUser('cashier');
        $this->actingAs($u);
        $day = Carbon::parse('2026-11-10');
        ShiftSchedule::create(['user_id' => $u->id, 'shift_date' => $day->toDateString(), 'start_time' => '08:00:00', 'end_time' => '17:00:00']);

        Carbon::setTestNow($day->copy()->setTime(7, 0));
        $this->post(route('cashier-shifts.store'), ['opening_cash' => 100])->assertSessionHas('error'); // ก่อนกะ

        Carbon::setTestNow($day->copy()->setTime(9, 0));
        $this->post(route('cashier-shifts.store'), ['opening_cash' => 100])->assertSessionHas('success');
        $this->assertNull(CashierShift::sellBlockReason($u));

        Carbon::setTestNow($day->copy()->setTime(17, 30));
        $this->assertNotNull(CashierShift::sellBlockReason($u));
    }

    // ---------- #17 รายงาน: ช่วงวันที่ ----------

    public function test_reports_include_the_whole_last_day_of_the_range(): void
    {
        $p = $this->product(['stock' => 10]);
        $this->postJson(route('sales.store'), [
            'items'          => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500,
        ])->assertOk();
        Sale::first()->forceFill(['created_at' => '2026-10-15 23:45:00'])->save();

        $in = $this->get(route('reports.index', ['start_date' => '2026-10-15', 'end_date' => '2026-10-15']));
        $this->assertEquals(200, $in->viewData('totalSales'));

        $out = $this->get(route('reports.index', ['start_date' => '2026-10-16', 'end_date' => '2026-10-16']));
        $this->assertEquals(0, $out->viewData('totalSales'));

        $this->get(route('reports.stock-movement', ['start_date' => '2026-10-15', 'end_date' => '2026-10-15']))->assertOk();
    }

    // ---------- ระบบหลัก (sanity) ----------

    public function test_sale_and_void_restore_stock(): void
    {
        $p = $this->product(['stock' => 10]);
        $this->postJson(route('sales.store'), [
            'items'          => [['product_id' => $p->id, 'quantity' => 4]],
            'payment_method' => 'cash', 'received_amount' => 1000,
        ])->assertOk();
        $this->assertSame(6, $p->fresh()->stock);
        $this->delete(route('sales.destroy', Sale::first()), ['void_reason' => 'test']);
        $this->assertSame(10, $p->fresh()->stock);
    }

    // ---------- รอบแก้บัค: กะ / ค่าใช้จ่าย / ใบรับเงิน ----------

    public function test_quotation_conversion_links_sale_to_open_shift(): void
    {
        $shift = $this->openShiftFor($this->admin);
        $q     = $this->quotation($this->product());

        $this->post(route('quotations.convert', $q), ['payment_method' => 'cash', 'received_amount' => 200]);

        $this->assertSame($shift->id, Sale::latest('id')->first()->cashier_shift_id);
    }

    public function test_drawer_expense_by_accountant_is_deducted_from_cashiers_shift(): void
    {
        $cashier = $this->makeUser('cashier');
        $shift   = $this->openShiftFor($cashier, ['opening_cash' => 1000]);

        $this->actingAs($this->makeUser('accountant'));
        $this->post(route('expenses.store'), [
            'expense_date' => now()->toDateString(), 'category' => 'other',
            'description'  => 'ค่าน้ำแข็ง', 'amount'            => 100, 'from_cash_drawer' => '1',
        ]);
        $this->assertSame($shift->id, Expense::first()->cashier_shift_id);

        $this->actingAs($cashier);
        $this->post(route('cashier-shifts.close', $shift), ['closing_cash' => 900]);
        $this->assertEquals(100, $shift->fresh()->total_expenses);
    }

    public function test_drawer_expense_without_open_shift_is_rejected(): void
    {
        $this->post(route('expenses.store'), [
            'expense_date' => now()->toDateString(), 'category' => 'other',
            'description'  => 'x', 'amount'                     => 100, 'from_cash_drawer' => '1',
        ])->assertSessionHas('error');
        $this->assertSame(0, Expense::count());
    }

    public function test_cannot_delete_drawer_expense_of_closed_shift(): void
    {
        $shift = $this->openShiftFor($this->admin, ['opened_at' => now()->subHours(3)]);
        $shift->update(['status' => 'closed', 'closed_at' => now()->subHour()]);
        $e = Expense::create([
            'expense_date'     => now()->toDateString(), 'category' => 'other', 'description'               => 'x', 'amount' => 50,
            'from_cash_drawer' => true, 'user_id'                   => $this->admin->id, 'cashier_shift_id' => $shift->id,
        ]);

        $this->delete(route('expenses.destroy', $e))->assertSessionHas('error');
        $this->assertNotNull(Expense::find($e->id));
    }

    public function test_cannot_void_cash_receipt_of_closed_shift(): void
    {
        $sale = Sale::create([
            'invoice_no' => 'INV-' . uniqid(), 'user_id' => $this->admin->id, 'subtotal' => 500, 'discount'          => 0,
            'total'      => 500, 'payment_method'        => 'credit', 'payment_status'   => 'partial', 'paid_amount' => 200,
            'status'     => 'completed',
        ]);
        $shift = $this->openShiftFor($this->admin, ['opened_at' => now()->subHours(3)]);
        $shift->update(['status' => 'closed', 'closed_at' => now()->subHour()]);
        $r = Receipt::create([
            'receipt_no'   => 'RC-' . uniqid(), 'sale_id'     => $sale->id, 'user_id'  => $this->admin->id,
            'payment_date' => now()->toDateString(), 'amount' => 200, 'payment_method' => 'cash', 'status' => 'active',
        ]);
        $r->forceFill(['created_at' => now()->subMinutes(90)])->save();

        $this->delete(route('receipts.destroy', $r), ['void_reason' => 'x']);

        $this->assertSame('active', $r->fresh()->status);
    }

    // ---------- รอบแก้บัค: ผู้ใช้ / PO ----------

    public function test_inactive_admin_does_not_count_as_a_remaining_admin(): void
    {
        $other = $this->makeUser('admin');
        $other->forceFill(['is_active' => false])->save();

        $this->put(route('users.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'cashier',
        ])->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_cannot_void_po_receipt_after_supplier_payment_and_cost_is_restored_otherwise(): void
    {
        $s  = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $p  = $this->product(['stock' => 0, 'cost' => 100]);
        $po = PurchaseOrder::create([
            'po_no'      => 'PO-C', 'supplier_id'           => $s->id, 'user_id'         => $this->admin->id,
            'order_date' => now()->toDateString(), 'status' => 'pending', 'total_amount' => 650,
        ]);
        $po->items()->create(['product_id' => $p->id, 'quantity' => 5, 'unit_cost' => 130, 'total' => 650]);

        $this->post(route('purchase-orders.receive', $po));
        $this->assertEquals(130, $p->fresh()->cost);
        $this->assertNotNull($po->fresh()->received_at);
        $doc = StockDocument::first();

        // จ่ายเงินแล้ว → ห้ามยกเลิกใบรับเข้า
        $this->post(route('purchase-orders.pay', $po), ['amount' => 100]);
        $this->delete(route('stock-documents.destroy', $doc));
        $this->assertSame('received', $po->fresh()->status);
        $this->assertSame(5, $p->fresh()->stock);

        // ยังไม่จ่าย → ยกเลิกได้ และต้นทุนกลับเป็นค่าเดิม
        $po->update(['paid_amount' => 0]);
        $this->delete(route('stock-documents.destroy', $doc));
        $this->assertSame('pending', $po->fresh()->status);
        $this->assertNull($po->fresh()->received_at);
        $this->assertEquals(100, $p->fresh()->cost);
    }

    public function test_ap_aging_counts_from_due_date_using_supplier_credit_term(): void
    {
        $s  = Supplier::create(['name' => 'Credit Sup', 'is_active' => true, 'credit_term_days' => 60]);
        $po = $this->receivedPo(1000, now()->subDays(100)->toDateString());
        $po->update(['supplier_id' => $s->id]);

        // ครบกำหนดเมื่อ 40 วันก่อน (100 - เครดิต 60) → ช่อง 31-60 วัน ไม่ใช่ 60+
        $totals = $this->get(route('reports.ap-aging'))->viewData('totals');
        $this->assertEquals(1000, $totals['days_31_60']);
        $this->assertEquals(0, $totals['days_60_plus']);

        // ไม่มีเครดิตเทอม → เกิน 60 วัน
        $s->update(['credit_term_days' => 0]);
        $totals = $this->get(route('reports.ap-aging'))->viewData('totals');
        $this->assertEquals(1000, $totals['days_60_plus']);
    }

    public function test_quotation_money_is_calculated_in_cents(): void
    {
        $p = $this->product();
        $this->post(route('quotations.store'), [
            'quote_date' => now()->toDateString(), 'valid_until' => now()->addDays(5)->toDateString(),
            'items'      => [['product_id' => $p->id, 'quantity' => 3, 'price' => 10.10]],
        ]);
        $q = Quotation::first();
        $this->assertEquals(30.30, (float) $q->total);
        $this->assertEquals(30.30, (float) $q->items()->first()->total);

        // ทศนิยมเกิน 2 ตำแหน่งต้องถูกปฏิเสธ
        $this->post(route('quotations.store'), [
            'quote_date' => now()->toDateString(), 'valid_until' => now()->addDays(5)->toDateString(),
            'items'      => [['product_id' => $p->id, 'quantity' => 1, 'price' => 10.005]],
        ])->assertSessionHasErrors('items.0.price');
    }

    public function test_po_receive_uses_weighted_average_cost_and_void_restores_it(): void
    {
        $s  = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $p  = $this->product(['stock' => 10, 'cost' => 100]);
        $po = PurchaseOrder::create([
            'po_no'      => 'PO-AVG', 'supplier_id'         => $s->id, 'user_id'         => $this->admin->id,
            'order_date' => now()->toDateString(), 'status' => 'pending', 'total_amount' => 1400,
        ]);
        $po->items()->create(['product_id' => $p->id, 'quantity' => 10, 'unit_cost' => 140, 'total' => 1400]);

        $this->post(route('purchase-orders.receive', $po));
        // (10×100 + 10×140) / 20 = 120
        $this->assertEquals(120, $p->fresh()->cost);
        $this->assertSame(20, $p->fresh()->stock);

        $this->delete(route('stock-documents.destroy', StockDocument::first()));
        $this->assertEquals(100, $p->fresh()->cost);
        $this->assertSame(10, $p->fresh()->stock);
    }

    public function test_po_cancel_requires_reason_and_records_it_and_delete_is_blocked(): void
    {
        $s  = Supplier::create(['name' => 'Sup', 'is_active' => true]);
        $po = PurchaseOrder::create([
            'po_no'      => 'PO-CAN', 'supplier_id'         => $s->id, 'user_id'         => $this->admin->id,
            'order_date' => now()->toDateString(), 'status' => 'pending', 'total_amount' => 0,
        ]);

        $this->post(route('purchase-orders.cancel', $po))->assertSessionHasErrors('cancel_reason');
        $this->assertSame('pending', $po->fresh()->status);

        $this->delete(route('purchase-orders.destroy', $po));
        $this->assertNotNull(PurchaseOrder::find($po->id));

        $this->post(route('purchase-orders.cancel', $po), ['cancel_reason' => 'สั่งผิดรุ่น']);
        $fresh = $po->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertSame('สั่งผิดรุ่น', $fresh->cancel_reason);
        $this->assertSame($this->admin->id, (int) $fresh->cancelled_by);
    }

    public function test_voided_sale_stays_in_stock_movement_report_with_reversal_row(): void
    {
        $p    = $this->product(['stock' => 5]);
        $sale = Sale::create([
            'invoice_no'      => 'INV-VOID-1', 'user_id' => $this->admin->id, 'subtotal'    => 10, 'discount'        => 0,
            'total'           => 10, 'payment_method'    => 'cash', 'payment_status'        => 'paid', 'paid_amount' => 10,
            'received_amount' => 10, 'change_amount'     => 0, 'status'                     => 'voided',
            'voided_at'       => now(), 'voided_by'      => $this->admin->id, 'void_reason' => 'ทดสอบ',
        ]);
        $sale->items()->create([
            'product_id' => $p->id, 'quantity' => 1, 'price' => 10, 'cost_at_sale' => 5, 'total' => 10, 'stock_after' => 4,
        ]);

        $this->get(route('reports.stock-movement'))
            ->assertOk()
            ->assertSee('INV-VOID-1')
            ->assertSee('ยกเลิกบิลขาย');
    }

    public function test_pos_lists_out_of_stock_products_but_cannot_sell_them(): void
    {
        $p = $this->product(['stock' => 0, 'name' => 'อะไหล่หมดสต็อกทดสอบ']);

        // หน้า POS ส่งสินค้าเป็น JSON (ชื่อไทยถูก escape เป็น \uXXXX) จึงตรวจด้วย SKU ที่เป็น ASCII
        $this->get(route('sales.index'))->assertOk()->assertSee($p->sku, false);

        $this->postJson(route('sales.store'), [
            'items'           => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method'  => 'cash',
            'received_amount' => 99999,
        ])->assertStatus(422);

        $this->assertSame(0, $p->fresh()->stock);
        $this->assertSame(0, Sale::count());
    }

    public function test_partial_return_of_cash_sale_restocks_and_refunds(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 4); // ยอด 800, สต็อกเหลือ 6
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash', 'reason' => 'เปลี่ยนใจ',
        ])->assertSessionHasNoErrors();

        $this->assertSame(7, $p->fresh()->stock);
        $r = \App\Models\SaleReturn::first();
        $this->assertEquals(200, $r->total_value);
        $this->assertEquals(200, $r->refund_amount);
        $this->assertSame('cash', $r->refund_method);
        $this->assertEquals(200, $sale->fresh()->returned_amount);
        $this->assertEquals(200, $sale->fresh()->refunded_amount);
        $this->assertSame('paid', $sale->fresh()->payment_status);
    }

    public function test_return_without_restock_does_not_increase_stock(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 2); // สต็อกเหลือ 8
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 0]],
            'refund_method' => 'transfer', 'reason' => 'ของชำรุด',
        ])->assertSessionHasNoErrors();

        $this->assertSame(8, $p->fresh()->stock);
        $this->assertNull(\App\Models\SaleReturnItem::first()->stock_after);
    }

    public function test_cannot_return_more_than_sold_including_previous_returns(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 3);
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 2, 'restock' => 1]],
            'refund_method' => 'cash', 'reason' => 'a',
        ]);
        $this->assertSame(1, \App\Models\SaleReturn::count());

        // คืนไปแล้ว 2 จากที่ซื้อ 3 → คืนเพิ่ม 2 ไม่ได้
        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 2, 'restock' => 1]],
            'refund_method' => 'cash', 'reason' => 'b',
        ])->assertSessionHas('error');
        $this->assertSame(1, \App\Models\SaleReturn::count());
        $this->assertSame(9, $p->fresh()->stock); // 10 - 3 + 2
    }

    public function test_bill_discount_is_distributed_and_full_return_refunds_exactly_total(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 2, ['discount' => 40]); // ยอดก่อนลด 400 ลด 40 = 360
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash', 'reason' => 'a',
        ]);
        $this->assertEquals(180, \App\Models\SaleReturn::first()->refund_amount);

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash', 'reason' => 'b',
        ]);
        $this->assertEquals(360, $sale->fresh()->refunded_amount);
        $this->assertEquals(360, $sale->fresh()->returned_amount);
    }

    public function test_refund_method_required_when_money_must_be_refunded(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 1);
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'  => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'reason' => 'a',
        ])->assertSessionHas('error');
        $this->assertSame(0, \App\Models\SaleReturn::count());
        $this->assertSame(9, $p->fresh()->stock);
    }

    public function test_return_on_unpaid_credit_sale_reduces_outstanding_without_refund(): void
    {
        $c    = Customer::create(['name' => 'ลูกค้าเชื่อ', 'is_active' => true]);
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 2, ['payment_method' => 'credit', 'customer_id' => $c->id, 'received_amount' => 0]);
        $item = $sale->items()->first(); // ยอด 400 ค้างทั้งหมด

        $this->post(route('sale-returns.store', $sale), [
            'items'  => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'reason' => 'คืนของ',
        ])->assertSessionHasNoErrors();

        $r = \App\Models\SaleReturn::first();
        $this->assertEquals(0, $r->refund_amount);
        $this->assertNull($r->refund_method);
        $this->assertEquals(200, $sale->fresh()->outstanding_amount);
        $this->assertSame('unpaid', $sale->fresh()->payment_status);

        // รับชำระเกินยอดค้างจริง (200) ไม่ได้
        $this->post(route('receipts.store'), [
            'sale_id' => $sale->id, 'payment_date' => now()->toDateString(),
            'amount'  => 300, 'payment_method'     => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->post(route('receipts.store'), [
            'sale_id' => $sale->id, 'payment_date' => now()->toDateString(),
            'amount'  => 200, 'payment_method'     => 'cash',
        ])->assertSessionHasNoErrors();
        $this->assertSame('paid', $sale->fresh()->payment_status);
    }

    public function test_partially_paid_credit_sale_return_refunds_only_the_excess(): void
    {
        $c    = Customer::create(['name' => 'ลูกค้าเชื่อ', 'is_active' => true]);
        $p    = $this->product(['stock' => 10, 'price' => 100]);
        $sale = $this->makeSale($p, 1, ['payment_method' => 'credit', 'customer_id' => $c->id, 'received_amount' => 0]);
        $sale->update(['paid_amount' => 70, 'payment_status' => 'partial']); // ยอด 100 จ่ายแล้ว 70
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash', 'reason' => 'คืนทั้งชิ้น',
        ])->assertSessionHasNoErrors();

        // คืนของ 100 แต่ลูกค้าจ่ายมา 70 → คืนเงิน 70 และไม่เหลือยอดค้าง
        $this->assertEquals(70, \App\Models\SaleReturn::first()->refund_amount);
        $this->assertEquals(0, $sale->fresh()->outstanding_amount);
        $this->assertSame('paid', $sale->fresh()->payment_status);
    }

    public function test_sale_with_return_cannot_be_voided(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 2);
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash', 'reason' => 'a',
        ]);

        $this->delete(route('sales.destroy', $sale), ['void_reason' => 'x'])->assertSessionHas('error');
        $this->assertSame('completed', $sale->fresh()->status);
    }

    public function test_cashier_can_return_but_delivery_cannot(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 1);

        $this->actingAs($this->makeUser('delivery'))
            ->get(route('sale-returns.index'))->assertForbidden();

        $this->actingAs($this->makeUser('cashier'))
            ->get(route('sale-returns.create', $sale))->assertStatus(200);
    }
}
