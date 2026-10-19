<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // นำเข้า Hash Facade
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('Aa123123'), // หรือใช้ bcrypt('password123')
            // 'email_verified_at' => now(), // เปิดใช้ถ้าตารางมีฟิลด์นี้และต้องการให้ยืนยันอีเมลแล้ว
        ]);
        $this->call([
            CategorySeeder::class,
            UnitSeeder::class,
            SupplierSeeder::class,
            BrandSeeder::class,
            CarBrandSeeder::class,
            CarModelSeeder::class,
            ProductSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }
}
