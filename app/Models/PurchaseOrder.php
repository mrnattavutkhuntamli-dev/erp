<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    // กำหนดฟิลด์ที่อนุญาตให้บันทึกข้อมูลแบบ Mass Assignment ได้
    protected $fillable = [
        'po_no', 'supplier_id', 'user_id', 'order_date',
        'expected_date', 'received_at', 'status', 'total_amount', 'paid_amount', 'note',
        'cancel_reason', 'cancelled_at', 'cancelled_by',
    ];
    protected $casts = [
        'order_date'    => 'date',
        'expected_date' => 'date',
        'received_at'   => 'datetime',
        'cancelled_at'  => 'datetime',
        'total_amount'  => 'decimal:2',
        'paid_amount'   => 'decimal:2',
    ];

    /** ยอดค้างจ่าย Supplier (ไม่ติดลบ) */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }

    public function payments()
    {
        return $this->hasMany(PurchaseOrderPayment::class);
    }

    public function stockDocuments()
    {
        return $this->hasMany(StockDocument::class);
    }
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
