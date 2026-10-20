<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'invoice_no', 'customer_id', 'user_id',
        'subtotal', 'discount', 'total',
        'payment_method', 'payment_status', 'paid_amount', 'due_date', // ✅ เพิ่ม
        'received_amount', 'change_amount', 'status',
    ];
    protected $casts = [
        'due_date' => 'date',
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
}
