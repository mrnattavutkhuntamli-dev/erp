<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'quote_no', 'customer_id', 'user_id', 'quote_date', 'valid_until',
        'subtotal', 'discount', 'total', 'status', 'note'
    ];
    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
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
