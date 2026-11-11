<?php
namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TwoFactorUnreadableSecretTest extends TestCase
{
    use RefreshDatabase;

    private function userWithSecret(?string $rawSecret): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $u = User::factory()->create(['google2fa_enabled' => true]);
        $u->assignRole('admin');
        // เขียนค่าดิบลง DB ตรง ๆ โดยไม่ผ่าน cast เพื่อจำลอง secret ที่เข้ารหัสด้วย APP_KEY อื่น
        DB::table('users')->where('id', $u->id)->update(['google2fa_secret' => $rawSecret]);

        return $u->fresh();
    }

    public function test_unreadable_secret_shows_friendly_error_instead_of_500(): void
    {
        $u = $this->userWithSecret('not-a-valid-encrypted-payload');
        $this->actingAs($u);

        $this->post(route('2fa.verify'), ['otp' => '123456'])
            ->assertSessionHasErrors('otp')
            ->assertRedirect();
        $this->assertNull(session('2fa_verified'));
    }

    public function test_unreadable_secret_does_not_consume_rate_limit(): void
    {
        $u = $this->userWithSecret('not-a-valid-encrypted-payload');
        $this->actingAs($u);

        for ($i = 0; $i < 7; $i++) {
            $this->post(route('2fa.verify'), ['otp' => '123456'])->assertSessionHasErrors('otp');
        }

        // ข้อความยังเป็นเรื่อง 2FA อ่านไม่ได้ ไม่ใช่ "ลองผิดหลายครั้งเกินไป"
        $this->assertStringNotContainsString('รอ', session('errors')->first('otp'));
    }

    public function test_missing_secret_is_handled_too(): void
    {
        $u = $this->userWithSecret(null);
        $this->actingAs($u);

        $this->post(route('2fa.verify'), ['otp' => '123456'])->assertSessionHasErrors('otp');
    }

    public function test_helper_returns_null_when_unreadable_and_value_when_readable(): void
    {
        $u = $this->userWithSecret('garbage');
        $this->assertNull($u->twoFactorSecretOrNull());

        // เขียนค่าที่เข้ารหัสถูกต้องลง DB ตรง ๆ (ไม่ใช้ save() เพราะ Eloquent จะถอดรหัสค่าเดิมที่เสียเพื่อเทียบก่อน)
        DB::table('users')->where('id', $u->id)->update(['google2fa_secret' => Crypt::encryptString('JBSWY3DPEHPK3PXP')]);
        $this->assertSame('JBSWY3DPEHPK3PXP', $u->fresh()->twoFactorSecretOrNull());
    }
}
