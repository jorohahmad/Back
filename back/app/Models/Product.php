<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table='products';
    protected $fillable = [
        'title',
        'description',
        'image1',
        'image2',
        'image3',
        'audio',
        'video',
        'insurance_amount',
        'governorate',
        'office',
        'owner_id',
        'is_for_sale',
        'sale_price',
        'is_for_rent',
        'rent_price_daily',
        'announcement',
        'repricing',
        'is_active',
        'stock',
        'delated'
    ];
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function items()
    {
        return $this->hasMany(ProductItem::class,'product_id');
    }
    protected $casts = [
        'sale_price' => 'double',
        'is_for_sale' => 'boolean', 
        'is_for_rent' => 'boolean', 
        'rent_price_daily' => 'double', 
        'announcement' => 'boolean',
        'repricing' => 'boolean',
        'is_active' => 'boolean',
        'delated' => 'boolean',
    ];
    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites', 'product_id', 'user_id')->withTimestamps();
    }
}
