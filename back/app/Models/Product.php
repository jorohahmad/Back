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
        'video',
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
    protected $casts = [
        'sale_price' => 'double', // أو 'float' كلاهما سيفي بالغرض
        'is_for_sale' => 'boolean', // يمكنك أيضاً التأكد من أن هذا الحقل يعود كـ true/false دائماً
        'is_for_rent' => 'boolean', // يمكنك أيضاً التأكد من أن هذا الحقل يعود كـ true/false دائماً
        'rent_price_daily' => 'double', // أو 'float' كلاهما سيفي بالغرض
    ];
}
