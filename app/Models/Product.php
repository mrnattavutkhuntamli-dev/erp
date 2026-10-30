<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id', 'category_id', 'unit_id', 'brand_id', 'sku', 'name', 'description',
        'cost', 'price', 'view_count', 'view_buy', 'stock', 'is_active',
    ];

    // สินค้า 1 ชิ้น อยู่ในหมวดหมู่ 1 หมวด
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // สินค้า 1 ชิ้น มาจากซัพพลายเออร์ 1 เจ้า
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // สินค้า 1 ชิ้น มีหน่วยนับ 1 หน่วย
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // สินค้า 1 ชิ้น มีได้หลายรูป
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    // ดึงเฉพาะรูปหน้าปก
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    // ฟังก์ชันความสัมพันธ์
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // ความสัมพันธ์แบบ Many-to-Many กับรุ่นรถ
    public function carModels()
    {
        return $this->belongsToMany(CarModel::class, 'product_car_model');
    }

    // ✅ สำหรับตรวจสอบก่อนลบ (บัค: ลบสินค้าแล้วประวัติหาย)
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockDocumentItems()
    {
        return $this->hasMany(StockDocumentItem::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }
}
