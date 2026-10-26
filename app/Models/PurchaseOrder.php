<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    // กำหนดฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment ได้
    protected $fillable = [
        'po_no', 'supplier_id', 'user_id', 'order_date',
        'expected_date', 'status', 'total_amount', 'note'
    ];
    protected $casts = [
        'order_date'    => 'date',
        'expected_date' => 'date',
    ];
    /**
     * ความสัมพันธ์แบบ One-to-Many (1 ใบสั่งซื้อ มีได้หลายรายการสินค้า)
     * ฝั่ง PurchaseOrder เป็นเจ้าของ (Has Many) PurchaseOrderItem
    */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * ความสัมพันธ์แบบ Many-to-One / Inverse One-to-Many (ใบสั่งซื้อนี้ เป็นของ 1 ผู้จำหน่าย)
     * ฝั่ง PurchaseOrder เป็นลูก (Belongs To) ของ Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * ความสัมพันธ์แบบ Many-to-One / Inverse One-to-Many (ใบสั่งซื้อนี้ สร้างโดย 1 ผู้ใช้งาน)
     * ฝั่ง PurchaseOrder เป็นลูก (Belongs To) ของ User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
