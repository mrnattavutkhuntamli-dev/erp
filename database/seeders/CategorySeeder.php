<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // กลุ่มของเหลวและเคมีภัณฑ์
            ['name' => 'น้ำมันเครื่องและของเหลว', 'slug' => 'oil-fluids', 'description' => 'น้ำมันเครื่อง, น้ำมันเกียร์, น้ำมันเบรก, น้ำยาหล่อเย็น'],

            // กลุ่มอะไหล่เครื่องยนต์
            ['name' => 'อะไหล่เครื่องยนต์', 'slug' => 'engine-parts', 'description' => 'ลูกสูบ, แหวนลูกสูบ, ปะเก็น, สายพาน, หัวเทียน'],

            // กลุ่มระบบกรอง
            ['name' => 'ไส้กรอง (Filters)', 'slug' => 'filters', 'description' => 'ไส้กรองน้ำมันเครื่อง, ไส้กรองอากาศ, ไส้กรองเชื้อเพลิง, ไส้กรองแอร์'],

            // กลุ่มระบบส่งกำลัง
            ['name' => 'ระบบส่งกำลังและคลัตช์', 'slug' => 'transmission-clutch', 'description' => 'ผ้าคลัตช์, ลูกปืน, เพลาขับ, ยางแท่นเครื่อง'],

            // กลุ่มระบบเบรก
            ['name' => 'ระบบเบรก', 'slug' => 'brake-system', 'description' => 'ผ้าเบรก, จานเบรก, คาลิปเปอร์, น้ำมันเบรก'],

            // กลุ่มระบบช่วงล่าง
            ['name' => 'ระบบช่วงล่างและบังคับเลี้ยว', 'slug' => 'suspension-steering', 'description' => 'โช้คอัพ, สปริง, ลูกหมาก, ปีกนก, ลูกปืนล้อ'],

            // กลุ่มระบบไฟฟ้า
            ['name' => 'ระบบไฟฟ้าและแบตเตอรี่', 'slug' => 'electrical-battery', 'description' => 'แบตเตอรี่, ไดร์ชาร์จ, มอเตอร์สตาร์ท, หลอดไฟ, ฟิวส์'],

            // กลุ่มยางและล้อ
            ['name' => 'ยางและล้อ', 'slug' => 'tires-wheels', 'description' => 'ยางรถยนต์, ยางรถไถ, ยางรถสิบล้อ, ยางมอเตอร์ไซต์, ล้อแม็ก'],

            // กลุ่มอุปกรณ์เสริมและตกแต่ง
            ['name' => 'อุปกรณ์เสริมและตกแต่ง', 'slug' => 'accessories', 'description' => 'กันชน, กระจกมองข้าง, เบาะ, พรมปูพื้น, ไฟตกแต่ง'],

            // กลุ่มอุปกรณ์สำหรับมอเตอร์ไซต์
            ['name' => 'อะไหล่มอเตอร์ไซต์', 'slug' => 'motorcycle-parts', 'description' => 'โซ่, สเตอร์, ผ้าเบรกมอเตอร์ไซต์, ลูกสูบมอเตอร์ไซต์'],

            // กลุ่มอุปกรณ์สำหรับรถเพื่อการเกษตร
            ['name' => 'อะไหล่รถไถและรถเพื่อการเกษตร', 'slug' => 'agricultural-parts', 'description' => 'อะไหล่รถไถเดินตาม, รถไถนั่งขับ, อุปกรณ์การเกษตร'],

            // กลุ่มอุปกรณ์สำหรับรถบรรทุก
            ['name' => 'อะไหล่รถบรรทุกและสิบล้อ', 'slug' => 'truck-parts', 'description' => 'อะไหล่รถสิบล้อ, ระบบลม, หัวฉีด, ปั๊มน้ำมันดีเซล'],
        ];

        foreach ($categories as $category) {
            // ใช้ updateOrCreate เพื่อป้องกันข้อมูลซ้ำกรณีรัน Seeder หลายรอบ
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
