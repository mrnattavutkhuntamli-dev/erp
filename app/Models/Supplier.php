<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'contact_name', 'phone', 'email', 'address', 'is_active']; // ฟิวที่อนุญาติให้สามารถบันทึกลงข้อมูล

    // ซัพพลายเออร์ 1 เจ้า ขายสินค้าให้เราได้หลายชิ้น
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
