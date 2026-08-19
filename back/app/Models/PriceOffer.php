<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceOffer extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'type',
        'proposed_price',
        'status',
    ];

    // للحصول على المشتري الذي قدم العرض
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // للحصول على الآلة المرتبطة بهذا العرض
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}