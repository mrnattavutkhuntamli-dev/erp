<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'เครื่องใช้ไฟฟ้า', 'slug' => 'electronics', 'description' => 'สินค้าประเภทอิเล็กทรอนิกส์'],
            ['name' => 'เครื่องเขียน', 'slug' => 'stationery', 'description' => 'อุปกรณ์สำนักงานและเครื่องเขียน'],
            ['name' => 'อุปกรณ์ไอที', 'slug' => 'it-equipment', 'description' => 'คอมพิวเตอร์และอุปกรณ์เสริม'],
            ['name' => 'เฟอร์นิเจอร์', 'slug' => 'furniture', 'description' => 'โต๊ะ เก้าอี้ และของตกแต่ง'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
