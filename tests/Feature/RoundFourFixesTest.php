<?php
namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Regression tests รอบที่ 4: รีเซ็ตรหัสผ่านต้องล้าง session, seeder ไม่ฝังรหัสผ่าน/รันซ้ำได้,
 * ยี่ห้อ/รุ่นรถห้ามซ้ำ + ปีต้องสมเหตุสมผล, บิลขายล็อกกะตอนบันทึก
 */
class RoundFourFixesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $u = User::factory()->create();
        $u->assignRole('admin');
        $u = $u->fresh();
        $this->actingAs($u);

        return $u;
    }

    public function test_password_reset_via_email_clears_other_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user  = User::factory()->create();
        $other = User::factory()->create();

        foreach ([['stolen', $user->id], ['other', $other->id]] as [$id, $uid]) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $uid, 'ip_address' => '1.1.1.1',
                'user_agent' => 'x', 'payload' => 'x', 'last_activity' => time(),
            ]);
        }

        $token = Password::createToken($user);
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'NewPassw0rd!x', 'password_confirmation' => 'NewPassw0rd!x',
        ])->assertRedirect('/login');

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $other->id)->count()); // ไม่กระทบคนอื่น
        $this->assertTrue(Hash::check('NewPassw0rd!x', $user->fresh()->password));
    }

    public function test_seeder_uses_env_or_random_password_and_is_rerunnable(): void
    {
        // ค่า env ถูกโหลดไปก่อนเทสต์เริ่ม ล้างไม่ได้ → ตรวจตามค่าที่ตั้งไว้จริงในเครื่องนั้น ๆ
        $configured = env('SEED_PASSWORD');

        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);

        if ($configured) {
            $this->assertTrue(Hash::check($configured, $admin->password), 'ต้องใช้รหัสผ่านจาก SEED_PASSWORD');
        } else {
            $this->assertFalse(Hash::check('Aa123123', $admin->password), 'ห้ามฝังรหัสผ่านตายตัวในโค้ด');
        }

        $before = User::count();
        $hash   = $admin->password;

        // รันซ้ำ: ต้องไม่ error (email ชน unique) ไม่สร้างซ้ำ และไม่ทับรหัสผ่านเดิม
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($before, User::count());
        $this->assertSame($hash, User::where('email', 'admin@example.com')->first()->password);
    }

    public function test_brand_and_car_brand_names_must_be_unique(): void
    {
        $this->admin();

        $this->post(route('brands.store'), ['name' => 'Bosch'])->assertSessionHasNoErrors();
        $this->post(route('brands.store'), ['name' => 'Bosch'])->assertSessionHasErrors('name');
        $this->assertSame(1, Brand::where('name', 'Bosch')->count());

        $other = Brand::create(['name' => 'Denso']);
        $this->put(route('brands.update', $other), ['name' => 'Bosch'])->assertSessionHasErrors('name');
        $this->put(route('brands.update', $other), ['name' => 'Denso'])->assertSessionHasNoErrors(); // ใช้ชื่อเดิมของตัวเองได้

        $this->post(route('car-brands.store'), ['name' => 'Toyota'])->assertSessionHasNoErrors();
        $this->post(route('car-brands.store'), ['name' => 'Toyota'])->assertSessionHasErrors('name');
        $this->assertSame(1, CarBrand::where('name', 'Toyota')->count());
    }

    public function test_car_model_validates_name_and_year_range(): void
    {
        $this->admin();
        $toyota = CarBrand::create(['name' => 'Toyota']);
        $honda  = CarBrand::create(['name' => 'Honda']);

        $this->post(route('car-models.store'), ['car_brand_id' => $toyota->id, 'name' => 'Vios', 'year_start' => 2013, 'year_end' => 2022])
            ->assertSessionHasNoErrors();

        // ชื่อรุ่นซ้ำในยี่ห้อเดียวกัน → ไม่ได้, ต่างยี่ห้อ → ได้
        $this->post(route('car-models.store'), ['car_brand_id' => $toyota->id, 'name' => 'Vios'])->assertSessionHasErrors('name');
        $this->post(route('car-models.store'), ['car_brand_id' => $honda->id, 'name' => 'Vios'])->assertSessionHasNoErrors();

        // ปีจบ < ปีเริ่ม / ปีเพี้ยน → ไม่ได้
        $this->post(route('car-models.store'), ['car_brand_id' => $toyota->id, 'name' => 'Yaris', 'year_start' => 2020, 'year_end' => 1990])
            ->assertSessionHasErrors('year_end');
        $this->post(route('car-models.store'), ['car_brand_id' => $toyota->id, 'name' => 'Yaris', 'year_start' => 20])
            ->assertSessionHasErrors('year_start');
        $this->assertSame(0, CarModel::where('name', 'Yaris')->count());

        $vios = CarModel::where('car_brand_id', $toyota->id)->where('name', 'Vios')->first();
        $this->put(route('car-models.update', $vios->id), ['name' => 'Vios', 'year_start' => 2013, 'year_end' => 2014])
            ->assertSessionHasNoErrors();
        $this->put(route('car-models.update', $vios->id), ['name' => 'Vios', 'year_start' => 2020, 'year_end' => 2019])
            ->assertSessionHasErrors('year_end');
    }

    public function test_pos_sale_is_bound_to_locked_open_shift_and_rejected_after_close(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        config(['pos.require_open_shift' => true]);

        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');
        $cashier = $cashier->fresh();
        $this->actingAs($cashier);

        $product = Product::create(['sku' => 'R4-1', 'name' => 'Pad', 'cost' => 100, 'price' => 200, 'stock' => 10, 'is_active' => true]);
        $shift   = CashierShift::create([
            'user_id' => $cashier->id, 'opened_at' => now()->subHour(), 'opening_cash' => 0, 'status' => 'open',
        ]);

        $payload = ['items' => [['product_id' => $product->id, 'quantity' => 1]], 'payment_method' => 'cash', 'received_amount' => 200];

        $this->postJson(route('sales.store'), $payload)->assertOk();
        $this->assertSame($shift->id, (int) \App\Models\Sale::first()->cashier_shift_id);

        // ปิดกะแล้วขายต่อไม่ได้ และสต็อกต้องไม่ถูกตัดเพิ่ม
        $shift->update(['status' => 'closed', 'closed_at' => now()]);
        $this->postJson(route('sales.store'), $payload)->assertStatus(422);
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertSame(1, \App\Models\Sale::count());
    }
}
