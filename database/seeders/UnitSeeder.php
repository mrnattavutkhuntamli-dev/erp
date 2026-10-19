<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            // หน่วยนับทั่วไป (อะไหล่แข็ง)
            ['name' => 'ชิ้น', 'short_name' => 'pcs'],
            ['name' => 'กล่อง', 'short_name' => 'box'],
            ['name' => 'แพ็ค', 'short_name' => 'pack'],
            ['name' => 'ชุด', 'short_name' => 'set'],       // เช่น ชุดซ่อม, ชุดคลัตช์

            // หน่วยนับสำหรับของเหลว (น้ำมัน, น้ำยา)
            ['name' => 'ลิตร', 'short_name' => 'L'],        // เช่น น้ำมันเครื่อง 1 ลิตร
            ['name' => 'ขวด', 'short_name' => 'bottle'],    // เช่น น้ำยาเช็ดกระจก
            ['name' => 'แกลลอน', 'short_name' => 'gal'],    // เช่น น้ำมันเครื่องแกลลอนใหญ่

            // หน่วยนับสำหรับของชั่งน้ำหนัก/วัดขนาด
            ['name' => 'กิโลกรัม', 'short_name' => 'kg'],
            ['name' => 'เมตร', 'short_name' => 'm'],        // เช่น สายยาง, สายพาน
            ['name' => 'ฟุต', 'short_name' => 'ft'],

            // หน่วยนับสำหรับยาง/ล้อ
            ['name' => 'เส้น', 'short_name' => 'pcs'],      // เช่น ยางรถยนต์ 1 เส้น
        ];

        foreach ($units as $unit) {
            // ใช้ updateOrCreate เพื่อป้องกันข้อมูลซ้ำกรณีรัน Seeder หลายรอบ
            Unit::updateOrCreate(
                ['short_name' => $unit['short_name']], // เงื่อนไขอ้างอิงจากชื่อย่อ
                $unit
            );
        }
    }
}
