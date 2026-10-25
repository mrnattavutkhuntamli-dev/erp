<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    // ใบเสร็จ

    protected $fillable = [
        'receipt_no', 'sale_id', 'user_id',
        'payment_date', 'amount', 'payment_method', 'note',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
        'idempotency_key',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'voided_at'    => 'datetime',
        'amount'       => 'decimal:2',
    ];

    // innert join orm คือ  belongsTo หรือ many -to - one
    // ใบเสร็จหลายใบสามารถมี sale คนขายได้ 1 คน
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    // ใบเสร็จหลายใบ สามารถมีลูกค้าได้ 1 คน belongsTo = inner join
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
