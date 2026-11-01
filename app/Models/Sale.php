<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'invoice_no', 'customer_id', 'user_id', 'cashier_shift_id',
        'subtotal', 'discount', 'total',
        'payment_method', 'payment_status', 'paid_amount', 'due_date',
        'received_amount', 'change_amount', 'status',
        'voided_at', 'voided_by', 'void_reason', 'idempotency_key',
    ];
    protected $casts = [
        'due_date'        => 'date',
        'voided_at'       => 'datetime',
        'subtotal'        => 'decimal:2',
        'discount'        => 'decimal:2',
        'total'           => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'received_amount' => 'decimal:2',
        'change_amount'   => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }
    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }
}
