<?php
namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ระบบรับคืนสินค้า (Sales Return)
 */
class SaleReturnTest extends TestCase
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

    private function makeSale(Product $p, int $qty, array $extra = []): Sale
    {
        $this->postJson(route('sales.store'), array_merge([
            'items'           => [['product_id' => $p->id, 'quantity' => $qty]],
            'payment_method'  => 'cash',
            'received_amount' => 100000,
        ], $extra))->assertOk();

        return Sale::latest('id')->first();
    }

    // ---------- tests ----------

    public function test_partial_return_of_cash_sale_restocks_and_refunds(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 4); // ยอด 800, สต็อกเหลือ 6
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'เปลี่ยนใจ',
        ])->assertSessionHasNoErrors();

        $this->assertSame(7, $p->fresh()->stock);

        $r = SaleReturn::first();
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
            'refund_method' => 'transfer',
            'reason'        => 'ของชำรุด',
        ])->assertSessionHasNoErrors();

        $this->assertSame(8, $p->fresh()->stock);
        $this->assertNull(SaleReturnItem::first()->stock_after);
    }

    public function test_cannot_return_more_than_sold_including_previous_returns(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 3);
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 2, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'a',
        ]);
        $this->assertSame(1, SaleReturn::count());

        // คืนไปแล้ว 2 จากที่ซื้อ 3 → คืนเพิ่ม 2 ไม่ได้
        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 2, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'b',
        ])->assertSessionHas('error');

        $this->assertSame(1, SaleReturn::count());
        $this->assertSame(9, $p->fresh()->stock); // 10 - 3 + 2
    }

    public function test_bill_discount_is_distributed_and_full_return_refunds_exactly_total(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 2, ['discount' => 40]); // ก่อนลด 400 ลด 40 = 360
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'a',
        ]);
        $this->assertEquals(180, SaleReturn::first()->refund_amount);

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'b',
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

        $this->assertSame(0, SaleReturn::count());
        $this->assertSame(9, $p->fresh()->stock);
    }

    public function test_return_on_unpaid_credit_sale_reduces_outstanding_without_refund(): void
    {
        $c    = Customer::create(['name' => 'ลูกค้าเชื่อ', 'is_active' => true]);
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 2, [
            'payment_method' => 'credit', 'customer_id' => $c->id, 'received_amount' => 0,
        ]);
        $item = $sale->items()->first(); // ยอด 400 ค้างทั้งหมด

        $this->post(route('sale-returns.store', $sale), [
            'items'  => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'reason' => 'คืนของ',
        ])->assertSessionHasNoErrors();

        $r = SaleReturn::first();
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
        $sale = $this->makeSale($p, 1, [
            'payment_method' => 'credit', 'customer_id' => $c->id, 'received_amount' => 0,
        ]);
        $sale->update(['paid_amount' => 70, 'payment_status' => 'partial']); // ยอด 100 จ่ายแล้ว 70
        $item = $sale->items()->first();

        $this->post(route('sale-returns.store', $sale), [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'คืนทั้งชิ้น',
        ])->assertSessionHasNoErrors();

        // คืนของมูลค่า 100 แต่ลูกค้าจ่ายมา 70 → คืนเงิน 70 และไม่เหลือยอดค้าง
        $this->assertEquals(70, SaleReturn::first()->refund_amount);
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
            'refund_method' => 'cash',
            'reason'        => 'a',
        ]);

        $this->delete(route('sales.destroy', $sale), ['void_reason' => 'x'])->assertSessionHas('error');
        $this->assertSame('completed', $sale->fresh()->status);
    }

    public function test_cashier_can_post_a_return_but_delivery_cannot(): void
    {
        $p    = $this->product(['stock' => 10, 'price' => 200]);
        $sale = $this->makeSale($p, 2);
        $item = $sale->items()->first();

        $payload = [
            'items'         => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => 1]],
            'refund_method' => 'cash',
            'reason'        => 'a',
        ];

        $this->actingAs($this->makeUser('delivery'))
            ->post(route('sale-returns.store', $sale), $payload)->assertForbidden();
        $this->assertSame(0, SaleReturn::count());

        $this->actingAs($this->makeUser('cashier'))
            ->post(route('sale-returns.store', $sale), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, SaleReturn::count());
        $saleReturn = SaleReturn::first();
        $cashier    = $this->makeUser('cashier');
        $this->actingAs($this->makeUser('cashier'))
            ->get(route('sale-returns.index'))->assertOk();

       $this->actingAs($cashier)
            ->get(route('sale-returns.show', $saleReturn))->assertOk();

        $this->actingAs($cashier)
            ->get(route('sales.show', $sale))->assertOk();
    }
}
