<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google2fa_secret',  // ✅ เพิ่ม
        'google2fa_enabled', // ✅ เพิ่ม
    ];
    // หมายเหตุ: is_active ไม่ใส่ใน fillable โดยตั้งใจ (เปลี่ยนผ่าน UserController::toggleActive เท่านั้น)

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'google2fa_enabled' => 'boolean',
            'is_active'         => 'boolean',
            'google2fa_secret' => 'encrypted',
        ];
    }

    /**
     * อ่าน secret ของ 2FA แบบไม่ throw: ถ้าถอดรหัสไม่ได้ (เช่น APP_KEY เปลี่ยนหลังตั้งค่า 2FA) จะคืน null
     */
    public function twoFactorSecretOrNull(): ?string
    {
        try {
            return $this->google2fa_secret ?: null;
        } catch (DecryptException $e) {
            return null;
        }
    }
}
