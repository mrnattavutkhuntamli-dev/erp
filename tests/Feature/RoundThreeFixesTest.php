<?php
namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression tests รอบที่ 3: remember_token, เพดานตัวเลข, รายงานลูกหนี้, idempotency ผูกผู้ใช้
 */
class RoundThreeFixesTest extends TestCase
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

    private function makeUser(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u->fresh();
    }

    private function product(array $o = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'T-' . uniqid(), 'name' => 'Brake Pad', 'cost' => 100, 'price' => 200,
            'stock' => 10, 'is_active' => true,
        ], $o));
    }

    // ---------- 1. remember_token ต้องถูกหมุน ----------

    public function test_changing_own_password_rotates_remember_token(): void
    {
        $this->admin->forceFill(['remember_token' => 'old-token'])->save();

        $this->put('/password', [
            'current_password'      => 'password',
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors();

        $this->assertNotSame('old-token', $this->admin->fresh()->remember_token);
    }

    public function test_admin_changing_password_or_role_rotates_remember_token(): void
    {
        $staff = $this->makeUser('cashier');
        $staff->forceFill(['remember_token' => 'old-token'])->save();

        $this->put(route('users.update', $staff), [
            'name' => $staff->name, 'email' => $staff->email, 'role' => 'cashier',
            'password' => 'another-pass-123', 'password_confirmation' => 'another-pass-123',
        ])->assertSessionHasNoErrors();
        $this->assertNotSame('old-token', $staff->fresh()->remember_token);

        $staff->forceFill(['remember_token' => 'old-token-2'])->save();
        $this->put(route('users.update', $staff), [
            'name' => $staff->name, 'email' => $staff->email, 'role' => 'stock',
        ])->assertSessionHasNoErrors();
        $this->assertNotSame('old-token-2', $staff->fresh()->remember_token);
    }

    public function test_editing_user_without_password_or_role_change_keeps_remember_token(): void
    {
        $staff = $this->makeUser('cashier');
        $staff->forceFill(['remember_token' => 'keep-me'])->save();

        $this->put(route('users.update', $staff), [
            'name' => 'Renamed', 'email' => $staff->email, 'role' => 'cashier',
        ])->assertSessionHasNoErrors();

        $this->assertSame('keep-me', $staff->fresh()->remember_token);
    }

    // ---------- 2. เพดานราคา/ต้นทุน/สต็อกของสินค้า ----------

    public function test_product_store_rejects_oversized_values(): void
    {
        $this->post(route('products.store'), ['name' => 'X', 'cost' => 1000000000, 'price' => 20, 'stock' => 1])
            ->assertSessionHasErrors('cost');
        $this->post(route('products.store'), ['name' => 'X', 'cost' => 10, 'price' => 1000000000, 'stock' => 1])
            ->assertSessionHasErrors('price');
        $this->post(route('products.store'), ['name' => 'X', 'cost' => 10, 'price' => 20, 'stock' => 1000000])
            ->assertSessionHasErrors('stock');
        $this->post(route('products.store'), ['name' => 'X', 'cost' => 10.555, 'price' => 20, 'stock' => 1])
            ->assertSessionHasErrors('cost');
        $this->assertSame(0, Product::count());
    }

    public function test_product_update_rejects_oversized_price(): void
    {
        $p = $this->product();

        $this->put(route('products.update', $p), ['name' => 'N', 'sku' => $p->sku, 'cost' => 100, 'price' => 1000000000])
            ->assertSessionHasErrors('price');
        $this->assertEquals(200, $p->fresh()->price);
    }

    // ---------- 3. เพดานยอดใน POS และกะ ----------

    public function test_pos_rejects_oversized_discount_and_received_amount(): void
    {
        $p = $this->product();

        $this->postJson(route('sales.store'), [
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500, 'discount' => 99999999999,
        ])->assertStatus(422)->assertJsonValidationErrors('discount');

        $this->postJson(route('sales.store'), [
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 99999999999,
        ])->assertStatus(422)->assertJsonValidationErrors('received_amount');

        $this->assertSame(0, Sale::count());
    }

    public function test_closing_shift_rejects_oversized_cash(): void
    {
        $shift = CashierShift::create([
            'user_id' => $this->admin->id, 'opened_at' => now()->subHour(),
            'opening_cash' => 0, 'status' => 'open',
        ]);

        $this->post(route('cashier-shifts.close', $shift), ['closing_cash' => 99999999999])
            ->assertSessionHasErrors('closing_cash');
        $this->assertSame('open', $shift->fresh()->status);
    }

    // ---------- 4. รายงานลูกหนี้: ยอดบิล − จ่าย = ค้าง ----------

    public function test_ar_aging_rows_add_up_after_return(): void
    {
        $p = $this->product();
        $c = Customer::create(['name' => 'Credit Co', 'is_active' => true]);

        $this->postJson(route('sales.store'), [
            'customer_id' => $c->id,
            'items' => [['product_id' => $p->id, 'quantity' => 2]], // 400
            'payment_method' => 'credit',
        ])->assertOk();
        $sale = Sale::latest('id')->first();

        $item = $sale->items()->first();
        $this->post(route('sale-returns.store', $sale), [
            'items'  => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]], // คืน 200
            'reason' => 'ทดสอบ',
        ])->assertSessionHasNoErrors();

        $res     = $this->get(route('reports.ar-aging'))->assertOk();
        $invoice = collect($res->viewData('agingData'))->first()['invoices'][0];

        $this->assertEquals(200.0, $invoice['total']); // 400 − รับคืน 200
        $this->assertEquals(0.0, $invoice['paid']);
        $this->assertEquals(200.0, $invoice['due']);
        $this->assertEquals($invoice['due'], round($invoice['total'] - $invoice['paid'], 2));
    }

    // ---------- 7. idempotency key ผูกกับผู้ใช้ที่ขาย ----------

    public function test_idempotency_key_does_not_return_another_users_sale(): void
    {
        $p   = $this->product();
        $key = (string) Str::uuid();

        $first = $this->postJson(route('sales.store'), [
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500, 'idempotency_key' => $key,
        ])->assertOk()->json('sale_id');

        $this->actingAs($this->makeUser('cashier'));
        $res = $this->postJson(route('sales.store'), [
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 500, 'idempotency_key' => $key,
        ]);

        $this->assertNotSame($first, $res->json('sale_id'));
        $this->assertSame(1, Sale::count());
        $this->assertSame(9, $p->fresh()->stock);
    }
}
