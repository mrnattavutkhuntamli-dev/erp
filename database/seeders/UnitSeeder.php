<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'ชิ้น', 'short_name' => 'pcs'],
            ['name' => 'กล่อง', 'short_name' => 'box'],
            ['name' => 'แพ็ค', 'short_name' => 'pack'],
            ['name' => 'กิโลกรัม', 'short_name' => 'kg'],
            ['name' => 'เมตร', 'short_name' => 'm'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
