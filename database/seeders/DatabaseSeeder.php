<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // รหัสผ่านเริ่มต้นอ่านจาก .env (SEED_PASSWORD) ถ้าไม่ตั้งไว้จะสุ่มใหม่ทุกครั้ง
        // และแสดงในหน้าจอ (ไม่ฝังรหัสผ่านตายตัวในโค้ดอีกต่อไป)
        $password = 'Aa123123';

        // 1. สร้าง Roles / Permissions ก่อน
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);

        // 2. สร้างผู้ใช้ตัวอย่าง [role, ชื่อ, อีเมล]
        $accounts = [
            ['admin',      'Administrator',      'admin@example.com'],
            ['cashier',    'พนักงานขาย 1',        'cashier1@example.com'],
            ['cashier',    'พนักงานขาย 2',        'cashier2@example.com'],
            ['cashier',    'พนักงานขาย 3',        'cashier3@example.com'],
            ['stock',      'พนักงานคลังสต็อก 1',   'stock1@example.com'],
            ['stock',      'พนักงานคลังสต็อก 2',   'stock2@example.com'],
            ['stock',      'พนักงานคลังสต็อก 3',   'stock3@example.com'],
            ['accountant', 'พนักงานบัญชี 1',       'accountant1@example.com'],
            ['accountant', 'พนักงานบัญชี 2',       'accountant2@example.com'],
            ['accountant', 'พนักงานบัญชี 3',       'accountant3@example.com'],
            ['delivery',   'พนักงานขนส่ง 1',       'delivery1@example.com'],
            ['delivery',   'พนักงานขนส่ง 2',       'delivery2@example.com'],
            ['delivery',   'พนักงานขนส่ง 3',       'delivery3@example.com'],
        ];

        foreach ($accounts as [$role, $name, $email]) {
            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($password),
            ]);
            $user->assignRole($role);
        }

        $this->command?->warn("รหัสผ่านเริ่มต้นของผู้ใช้ตัวอย่างทั้งหมด: {$password}  (เปลี่ยนก่อนใช้งานจริง)");

        // 3. รัน Seeder ที่เหลือ
        $this->call([
            CategorySeeder::class,
            UnitSeeder::class,
            SupplierSeeder::class,
            BrandSeeder::class,
            CarBrandSeeder::class,
            CarModelSeeder::class,
            ProductSeeder::class,
            ShiftScheduleSeeder::class,
        ]);
    }
}
