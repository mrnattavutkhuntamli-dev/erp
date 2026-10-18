<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'บริษัท ไอที ซัพพลาย จำกัด',
                'contact_name' => 'คุณสมชาย',
                'phone' => '02-123-4567',
                'email' => 'contact@itsupply.com',
                'address' => '123 ถนนสุขุมวิท กรุงเทพฯ',
                'is_active' => true
            ],
            [
                'name' => 'ร้านเครื่องเขียนพี่หนู',
                'contact_name' => 'คุณหนู',
                'phone' => '081-234-5678',
                'email' => 'nhoo@stationery.com',
                'address' => '456 ถนนพญาไท กรุงเทพฯ',
                'is_active' => true
            ],
            [
                'name' => 'บริษัท เฟอร์นิเจอร์ เฟอร์เฟกต์',
                'contact_name' => 'คุณวิชัย',
                'phone' => '02-987-6543',
                'email' => 'sales@perfectfurniture.com',
                'address' => '789 ถนนรัชดาภิเษก กรุงเทพฯ',
                'is_active' => true
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
