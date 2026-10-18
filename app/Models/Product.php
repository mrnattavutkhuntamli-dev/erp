<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * ฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment
     *
     * @var array<int, string>
    */
    protected $fillable = [
        'supplier_id', 'category_id', 'unit_id', 'sku', 'name', 'description',
        'cost', 'price', 'view_count', 'view_buy', 'stock', 'is_active'
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
    // ฟังก์ชันสำหรับสร้าง SKU อัตโนมัติ
    public static function generateSku()
    {
        do {
            // สุ่มตัวเลข 4 หลัก (เช่น 1000 - 9999)
            $sku = 'PRD-' . rand(1000, 9999);

            // เช็คว่ามีรหัสนี้ในฐานข้อมูลหรือยัง (ถ้ามีให้วนลูปสุ่มใหม่)
        } while (self::where('sku', $sku)->exists());

        return $sku;
    }
}
