<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'short_name']; // ฟิวที่อนุญาติให้สามารถบันทึกลงข้อมูล

    // หน่วยนับ 1 หน่วย ถูกใช้กับสินค้าได้หลายชิ้น
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
