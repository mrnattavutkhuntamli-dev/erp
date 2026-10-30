<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // รีเซ็ตแคชสิทธิ์ก่อน
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. สร้าง Permissions (แยกตามกลุ่มการทำงานของ ERP)
        $permissions = [
            // สินค้าและข้อมูลหลัก
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'manage-master-data', // จัดการหมวดหมู่, ยี่ห้อ, รุ่นรถ, หน่วยนับ

            // คลังสินค้า
            'view-stock', 'create-stock-document', 'void-stock-document',
            'adjust-stock', // ปรับปรุงสต็อก (แยกจากการสร้างเอกสารรับ/เบิก)

            // การขาย
            'view-sales', 'create-sale', 'void-sale',

            // การเงิน
            'manage-receipts', 'void-receipt', 'manage-expenses',

            // ลูกค้าและซัพพลายเออร์
            'manage-customers', 'manage-suppliers',

            // รายงานและระบบ
            'view-reports', 'manage-users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. สร้าง Roles และกำหนดสิทธิ์ให้แต่ละตำแหน่ง
        // ตำแหน่งที่ 1: เจ้าของร้าน/ผู้จัดการ (Admin)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // ตำแหน่งที่ 2: พนักงานขายหน้าเคาน์เตอร์ (Cashier)
        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->syncPermissions([
            'view-products', 'view-stock',
            'view-sales', 'create-sale', 'manage-receipts',
            'manage-customers'
            // ไม่มี void-sale: การยกเลิกบิลต้องเป็น admin
        ]);

        // ตำแหน่งที่ 3: พนักงานคลังสินค้า/จัดสินค้า (Stock)
        $stock = Role::firstOrCreate(['name' => 'stock']);
        $stock->syncPermissions([
            'view-products', 'view-stock', 'create-stock-document', 'adjust-stock',
        ]);

        // ตำแหน่งที่ 4: พนักงานบัญชี (Accountant)
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->syncPermissions([
            'view-sales', 'view-reports', 'view-stock',
            'manage-receipts', 'void-receipt', 'manage-expenses',
        ]);

        // ตำแหน่งที่ 5: พนักงานยกของ/ขนส่ง (Delivery)
        $delivery = Role::firstOrCreate(['name' => 'delivery']);
        $delivery->syncPermissions([
            'view-stock', 'view-sales'
        ]);

        // 3. กำหนด Role ให้ User คนแรก (สมมติว่าเป็นเจ้าของร้าน)
        $adminUser = User::first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }
    }
}
