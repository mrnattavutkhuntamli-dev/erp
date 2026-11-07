<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = [
        'return_no', 'sale_id', 'user_id', 'cashier_shift_id',
        'total_value', 'refund_amount', 'refund_method', 'reason',
        'status', 'voided_at', 'voided_by', 'void_reason', 'idempotency_key',
    ];

    protected $casts = [
        'total_value'   => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'voided_at'     => 'datetime',
    ];

     public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
