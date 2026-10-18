<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class StockDocument extends Model
{
    use HasFactory;
    protected $fillable = [
        'doc_no', 'type', 'date', 'receiver_name', 'note', 'user_id'
    ];
    public function items()
    {
        return $this->hasMany(StockDocumentItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
