<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\CarModel;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ดึง ID ของข้อมูลที่ต้องอ้างอิง
        $catOil = Category::where('name', 'น้ำมันเครื่อง')->first() ?? Category::first();
        $catFilter = Category::where('name', 'ไส้กรอง')->first() ?? Category::first();

        $sup1 = Supplier::first();
        $unitPiece = Unit::where('short_name', 'pcs')->first() ?? Unit::first();
        $unitLiter = Unit::where('short_name', 'l')->first() ?? Unit::first();

        $brandDenso = Brand::where('name', 'Denso')->first();
        $brandMotul = Brand::where('name', 'Motul')->first();
        $brandBosch = Brand::where('name', 'Bosch')->first();

        $modelVigo = CarModel::where('name', 'Vigo')->first();
        $modelDmax = CarModel::where('name', 'D-Max')->first();
        $modelCivic = CarModel::where('name', 'Civic')->first();

        // 1. สร้างสินค้า: ไส้กรองน้ำมันเครื่อง (Denso)
        $p1 = Product::create([
            'supplier_id' => $sup1->id,
            'category_id' => $catFilter->id,
            'unit_id' => $unitPiece->id,
            'brand_id' => $brandDenso->id,
            'sku' => 'PRD-FLT-001',
            'name' => 'ไส้กรองน้ำมันเครื่อง Denso',
            'description' => 'ไส้กรองน้ำมันเครื่องคุณภาพสูง',
            'cost' => 150.00,
            'price' => 250.00,
            'stock' => 50,
            'is_active' => true,
        ]);
        // เชื่อมรุ่นรถ
        $p1->carModels()->sync([$modelVigo->id, $modelDmax->id]);

        // 2. สร้างสินค้า: น้ำมันเครื่อง Motul 5W-30
        $p2 = Product::create([
            'supplier_id' => $sup1->id,
            'category_id' => $catOil->id,
            'unit_id' => $unitLiter->id,
            'brand_id' => $brandMotul->id,
            'sku' => 'PRD-OIL-001',
            'name' => 'น้ำมันเครื่อง Motul 8100 5W-30 (1L)',
            'description' => 'น้ำมันเครื่องสังเคราะห์แท้ 100%',
            'cost' => 450.00,
            'price' => 650.00,
            'stock' => 20,
            'is_active' => true,
        ]);
        $p2->carModels()->sync([$modelCivic->id]);

        // 3. สร้างสินค้า: หัวเทียน Bosch
        $p3 = Product::create([
            'supplier_id' => $sup1->id,
            'category_id' => $catFilter->id,
            'unit_id' => $unitPiece->id,
            'brand_id' => $brandBosch->id,
            'sku' => 'PRD-SPK-001',
            'name' => 'หัวเทียน Bosch Platinum',
            'description' => 'หัวเทียนแพลตตินั่ม',
            'cost' => 120.00,
            'price' => 200.00,
            'stock' => 5, // ตั้งให้ต่ำกว่า 10 เพื่อทดสอบแจ้งเตือน Dashboard
            'is_active' => true,
        ]);
        $p3->carModels()->sync([$modelVigo->id, $modelCivic->id]);
    }
}
