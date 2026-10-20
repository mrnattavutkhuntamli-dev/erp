<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adj_no',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reason',
        'note',
    ];

    /**
     * ความสัมพันธ์: การปรับปรุงนี้เป็นของสินค้า 1 ชิ้น
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ความสัมพันธ์: การปรับปรุงนี้ถูกทำโดยพนักงาน 1 คน
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
