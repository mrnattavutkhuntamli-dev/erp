<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockDocument extends Model
{
    use HasFactory;
    protected $fillable = [
        'doc_no',
        'type',
        'date',
        'supplier_id',
        'receiver_name',
        'note',
        'status', // <-- เพิ่มอันนี้
        'user_id',
    ];
    public function items()
    {
        return $this->hasMany(StockDocumentItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
