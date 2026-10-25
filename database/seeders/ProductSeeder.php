<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ดึงข้อมูลอ้างอิงทั้งหมดมาเตรียมไว้
        $categories = Category::all();
        $suppliers  = Supplier::all();
        $units      = Unit::all();
        $brands     = Brand::all();
        $carModels  = CarModel::all();

        // ถ้าข้อมูลหลักยังว่างอยู่ ให้หยุดทำงานก่อน
        if ($categories->isEmpty() || $suppliers->isEmpty() || $units->isEmpty()) {
            $this->command->warn('ไม่สามารถ Seed สินค้าได้ เนื่องจากข้อมูลหลัก (Category/Supplier/Unit) ยังว่างอยู่');
            return;
        }

        // คำศัพท์สำหรับแต่งชื่อสินค้า
        $prefixes = ['ไส้กรอง', 'น้ำมันเครื่อง', 'หัวเทียน', 'ผ้าเบรก', 'โช้คอัพ', 'ลูกปืน', 'สายพาน', 'กรองอากาศ', 'ปั๊มน้ำ', 'คอยล์'];
        $suffixes = ['แท้', 'เทียม', 'Premium', 'Sport', 'Standard', 'Heavy Duty', 'Ultra', 'Eco', 'Pro', 'Max'];

        $this->command->info('กำลังสร้างสินค้า 1,000 รายการ...');
        $bar = $this->command->getOutput()->createProgressBar(1000);
        $bar->start();

        for ($i = 1; $i <= 1000; $i++) {
            // สุ่มเลือกข้อมูลอ้างอิง
            $category = $categories->random();
            $supplier = $suppliers->random();
            $unit     = $units->random();
            $brand    = $brands->isNotEmpty() ? $brands->random() : null;

            // สร้างชื่อสินค้าแบบสุ่ม
            $productName = $prefixes[array_rand($prefixes)] . ' ' .
                           $brand?->name . ' ' .
                           $suffixes[array_rand($suffixes)] . ' ' .
                           Str::random(3);

            // คำนวณราคา
            $cost  = rand(50, 5000); // ต้นทุน 50 - 5000 บาท
            $price = $cost + ($cost * rand(10, 50) / 100); // ราคาขายบวกกำไร 10-50%

            // สร้าง SKU แบบไม่ให้ซ้ำ
            $sku = 'PRD-' . strtoupper(Str::random(3)) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);

            // สร้างสินค้า
            $product = Product::create([
                'supplier_id' => $supplier->id,
                'category_id' => $category->id,
                'unit_id'     => $unit->id,
                'brand_id'    => $brand?->id,
                'sku'         => $sku,
                'name'        => $productName,
                'description' => 'สินค้าจำลองสำหรับทดสอบระบบ หมายเลข ' . $i,
                'cost'        => $cost,
                'price'       => $price,
                'stock'       => rand(0, 200), // สุ่มสต็อก 0-200 ชิ้น
                'is_active'   => rand(1, 100) > 5, // 95% เปิดใช้งาน
            ]);

            // สุ่มเชื่อมรุ่นรถ (ถ้ามี)
            if ($carModels->isNotEmpty()) {
                // สุ่มเลือกรถ 1-3 รุ่นที่สินค้านี้ใช้ได้
                $randomModels = $carModels->random(rand(1, min(3, $carModels->count())));
                $product->carModels()->sync($randomModels->pluck('id')->toArray());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('✅ สร้างสินค้า 1,000 รายการเสร็จเรียบร้อย!');
    }
}
