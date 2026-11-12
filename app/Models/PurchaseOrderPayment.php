<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderPayment extends Model
{
    protected $fillable = [
        'purchase_order_id', 'user_id', 'amount', 'status',
        'idempotency_key', 'voided_at', 'voided_by',
        'payment_date', 'payment_method', 'note',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'voided_at'    => 'datetime',
        'payment_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
