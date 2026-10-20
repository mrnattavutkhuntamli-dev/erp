<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    // กำหนดฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment ได้
    protected $fillable = [
        'purchase_order_id', 'product_id', 'quantity', 'unit_cost', 'total'
    ];

    /**
     * ความสัมพันธ์แบบ Many-to-One / Inverse One-to-Many
     * รายการสินค้านี้ เป็นของ PurchaseOrder (ใบสั่งซื้อ) 1 ใบ
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * ความสัมพันธ์แบบ Many-to-One / Inverse One-to-Many
     * รายการสินค้านี้ อ้างอิงถึง Product (สินค้า) 1 รายการ
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
