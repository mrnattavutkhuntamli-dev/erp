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
                'name' => 'บริษัท สยามอะไหล่ยนต์ จำกัด (มหาชน)',
                'contact_name' => 'คุณวิชัย (ฝ่ายขาย)',
                'phone' => '02-555-1234',
                'email' => 'sales@siamautoparts.co.th',
                'address' => '88/9 ถนนพระราม 2 แขวงแสมดำ เขตบางขุนเทียน กรุงเทพฯ 10150',
                'is_active' => true
            ],
            [
                'name' => 'หจก. อีซูซุเพาเวอร์พาร์ท',
                'contact_name' => 'คุณสมชาย',
                'phone' => '081-999-8888',
                'email' => 'isuzu.power@hotmail.com',
                'address' => '123 หมู่ 4 ต.บางพลีใหญ่ อ.บางพลี จ.สมุทรปราการ 10540',
                'is_active' => true
            ],
            [
                'name' => 'โตโยต้าคาร์พาร์ท ดิสทริบิวชั่น',
                'contact_name' => 'คุณมาลี',
                'phone' => '02-777-6666',
                'email' => 'toyota.parts@tcpd.com',
                'address' => '456 ถนนบางนา-ตราด กม. 12 เขตบางนา กรุงเทพฯ 10260',
                'is_active' => true
            ],
            [
                'name' => 'ร้านพี่ใหญ่ ยางยนต์และอะไหล่',
                'contact_name' => 'พี่ใหญ่',
                'phone' => '089-111-2222',
                'email' => 'piyai.tires@gmail.com',
                'address' => '789 ถนนเพชรเกษม เขตภาษีเจริญ กรุงเทพฯ 10160',
                'is_active' => true
            ],
            [
                'name' => 'บริษัท มอเตอร์ไซค์ พาร์ทส์ ซัพพลาย จำกัด',
                'contact_name' => 'คุณอนันต์',
                'phone' => '02-444-5555',
                'email' => 'contact@moto-parts.co.th',
                'address' => '99/1 ถนนศรีนครินทร์ เขตสวนหลวง กรุงเทพฯ 10250',
                'is_active' => true
            ],
            [
                'name' => 'หจก. รถไถการเกษตรพาณิชย์',
                'contact_name' => 'คุณประเสริฐ',
                'phone' => '056-333-444',
                'email' => 'agri.machinery@yahoo.com',
                'address' => '55 หมู่ 2 ต.นครสวรรค์ตก อ.เมือง จ.นครสวรรค์ 60000',
                'is_active' => true
            ],
        ];

        foreach ($suppliers as $supplier) {
            // ใช้ updateOrCreate เพื่อป้องกันข้อมูลซ้ำ
            Supplier::updateOrCreate(
                ['name' => $supplier['name']], // เงื่อนไขในการเช็คว่ามีอยู่แล้วหรือยัง
                $supplier // ข้อมูลที่จะบันทึกหรืออัปเดต
            );
        }
    }
}
