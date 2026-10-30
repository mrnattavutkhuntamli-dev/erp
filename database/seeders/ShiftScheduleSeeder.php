<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\Carbon;

class ShiftScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ 1. ดึงเฉพาะแคชเชียร์ทั้งหมด
        $employees = User::whereHas('roles', function ($q) {
            $q->where('name', 'cashier');
        })->get();

        if ($employees->isEmpty()) {
            $this->command->warn('ไม่พบแคชเชียร์ในระบบ กรุณาสร้างก่อน');
            return;
        }

        $totalEmployees = $employees->count();
        $this->command->info('พบแคชเชียร์ ' . $totalEmployees . ' คน');
        $this->command->info('กำลังสร้างตารางกะแบบสลับเวร...');

        // 2. ตั้งค่าช่วงวันที่ (วันนี้ ถึง 30 วันข้างหน้า)
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(30);

        // 3. ลบตารางกะเก่าออกก่อน (ป้องกันข้อมูลซ้ำ)
        ShiftSchedule::whereBetween('shift_date', [$startDate, $endDate])->delete();

        // 4. วนลูปตามวัน
        $employeeIndex = 0; // ตัวนับสำหรับสลับเวร

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            // ข้ามวันอาทิตย์ (หยุด)
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            // ✅ สลับเวร: แต่ละวันเลือกแคชเชียร์ 1-2 คน (ไม่ให้ทุกคนทำงานพร้อมกัน)
            // สมมติให้มี 2 กะต่อวัน (เช้า/บ่าย)
            // - กะเช้า: 08:00-14:00
            // - กะบ่าย: 14:00-20:00

            // วันคี่: คนที่ 1 เข้ากะเช้า, คนที่ 2 เข้ากะบ่าย
            // วันคู่: คนที่ 3 เข้ากะเช้า, คนที่ 4 เข้ากะบ่าย
            // (ถ้ามีแค่ 1-2 คน ก็ให้สลับกันไป)

            $morningEmployee = $employees[$employeeIndex % $totalEmployees];
            $afternoonEmployee = $employees[($employeeIndex + 1) % $totalEmployees];

            // กะเช้า
            ShiftSchedule::create([
                'user_id'    => $morningEmployee->id,
                'shift_date' => $date->toDateString(),
                'start_time' => '08:00:00',
                'end_time'   => '14:00:00',
                'note'       => 'กะเช้า',
            ]);

            // กะบ่าย (ถ้ามีแคชเชียร์มากกว่า 1 คน)
            if ($totalEmployees > 1) {
                ShiftSchedule::create([
                    'user_id'    => $afternoonEmployee->id,
                    'shift_date' => $date->toDateString(),
                    'start_time' => '14:00:00',
                    'end_time'   => '20:00:00',
                    'note'       => 'กะบ่าย',
                ]);
            }

            $employeeIndex += 2; // เลื่อนไป 2 คนในวันถัดไป
        }

        $this->command->info('✅ สร้างตารางกะเสร็จเรียบร้อย!');
    }
}
