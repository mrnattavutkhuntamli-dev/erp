<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. สร้าง Admin
        $admin = User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@example.com',
            'password' => Hash::make('Aa123123'),
        ]);

        // 2. รัน Role Seeder ก่อน
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);

        // 3. ให้ Role admin
        $admin->assignRole('admin');

        // 4. สร้างแคชเชียร์ 3 คน
        $cashier1 = User::create([
            'name'     => 'พนักงานขาย 1',
            'email'    => 'cashier1@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $cashier1->assignRole('cashier');

        $cashier2 = User::create([
            'name'     => 'พนักงานขาย 2',
            'email'    => 'cashier2@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $cashier2->assignRole('cashier');

        $cashier3 = User::create([
            'name'     => 'พนักงานขาย 3',
            'email'    => 'cashier3@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $cashier3->assignRole('cashier');

        // 5. สร้างพนักงานคลัง 3 คน
        $stock1 = User::create([
            'name'     => 'พนักงานคลังสต็อก 1',
            'email'    => 'stock1@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $stock1->assignRole('stock');

        $stock2 = User::create([
            'name'     => 'พนักงานคลังสต็อก 2',
            'email'    => 'stock2@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $stock2->assignRole('stock');

        $stock3 = User::create([
            'name'     => 'พนักงานคลังสต็อก 3',
            'email'    => 'stock3@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $stock3->assignRole('stock');

        // ✅ 6. สร้างพนักงานบัญชี 3 คน (แก้บัคซ้ำ)
        $accountant1 = User::create([
            'name'     => 'พนักงานบัญชี 1',
            'email'    => 'accountant1@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $accountant1->assignRole('accountant');

        $accountant2 = User::create([
            'name'     => 'พนักงานบัญชี 2',
            'email'    => 'accountant2@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $accountant2->assignRole('accountant');

        $accountant3 = User::create([
            'name'     => 'พนักงานบัญชี 3',
            'email'    => 'accountant3@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $accountant3->assignRole('accountant');

        $delivery1 = User::create([
            'name'     => 'พนักงานขนส่ง 1', // ✅
            'email'    => 'delivery1@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $delivery1->assignRole('delivery');

        $delivery2 = User::create([
            'name'     => 'พนักงานขนส่ง 2', // ✅
            'email'    => 'delivery2@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $delivery2->assignRole('delivery');

        $delivery3 = User::create([
            'name'     => 'พนักงานขนส่ง 3', // ✅
            'email'    => 'delivery3@example.com',
            'password' => Hash::make('Aa123123'),
        ]);
        $delivery3->assignRole('delivery');

        // 7. รัน Seeder ที่เหลือ
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
