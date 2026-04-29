<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table='products';
    protected $fillable = [
        'title',
        'description',
        'image',
        'audio',
        'owner_id',
        'is_for_sale',
        'sale_price',
        'is_for_rent',
        'rent_price_daily',
    ];
    // الحصول على صاحب الآلة
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    // الحصول على العناصر المرتبطة بالآلة
    public function items()
    {
        return $this->hasMany(ProductItem::class,'product_id');
    }
}
