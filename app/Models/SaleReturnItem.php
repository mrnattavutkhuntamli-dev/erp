<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id', 'sale_item_id', 'product_id',
        'quantity', 'value', 'restock', 'stock_after',
    ];

    protected $casts = [
        'value'   => 'decimal:2',
        'restock' => 'boolean',
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
