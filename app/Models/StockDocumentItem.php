<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class StockDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_document_id', 'product_id', 'quantity', 'stock_after'
    ];
    public function document()
    {
        return $this->belongsTo(StockDocument::class, 'stock_document_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
