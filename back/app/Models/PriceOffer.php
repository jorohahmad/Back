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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}