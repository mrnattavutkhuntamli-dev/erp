<?php
namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests รอบที่ 7: แคชเชียร์ต้องเปิดกะก่อนจึงขายได้ "โดยค่าเริ่มต้น"
 * (ไม่ override config ใน test นี้ เพื่อตรวจค่า default จริงของระบบ)
 */
class RoundSevenFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u->fresh();
    }

    private function product(): Product
    {
        return Product::create([
            'sku' => 'R7-' . uniqid(), 'name' => 'Pad', 'cost' => 100, 'price' => 200,
            'stock' => 10, 'is_active' => true,
        ]);
    }

    private function payload(Product $p): array
    {
        return [
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cash', 'received_amount' => 200,
        ];
    }

    public function test_shift_is_required_by_default(): void
    {
        $this->assertTrue(config('pos.require_open_shift'));
    }

    public function test_cashier_without_open_shift_cannot_sell(): void
    {
        $p = $this->product();
        $this->actingAs($this->makeUser('cashier'));

        $this->postJson(route('sales.store'), $this->payload($p))->assertStatus(422);

        $this->assertSame(0, Sale::count());
        $this->assertSame(10, $p->fresh()->stock);
    }

    public function test_pos_page_redirects_cashier_without_shift(): void
    {
        $this->actingAs($this->makeUser('cashier'));

        $this->get(route('sales.index'))->assertRedirect(route('cashier-shifts.index'));
    }

    public function test_cashier_with_open_shift_can_sell(): void
    {
        $p       = $this->product();
        $cashier = $this->makeUser('cashier');
        $shift   = CashierShift::create([
            'user_id' => $cashier->id, 'opened_at' => now()->subHour(),
            'opening_cash' => 0, 'status' => 'open',
        ]);
        $this->actingAs($cashier);

        $this->postJson(route('sales.store'), $this->payload($p))->assertOk();

        $this->assertSame($shift->id, (int) Sale::first()->cashier_shift_id);
    }

    public function test_admin_can_still_sell_without_shift(): void
    {
        $p = $this->product();
        $this->actingAs($this->makeUser('admin'));

        $this->postJson(route('sales.store'), $this->payload($p))->assertOk();

        $this->assertSame(1, Sale::count());
    }
}
