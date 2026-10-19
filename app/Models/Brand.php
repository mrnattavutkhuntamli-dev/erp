<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['name', 'logo'];

    // แบรนด์ 1 แบรนด์ มีสินค้าได้หลายชิ้น
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
