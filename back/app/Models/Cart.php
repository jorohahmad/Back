<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'type',
        'status',
        'quantity',
        'unit_price',
        'rent_start_date',
        'rent_end_date',
        'rent_days',
    ];

    
    protected $casts = [
        'rent_start_date' => 'date',
        'rent_end_date' => 'date',
        'unit_price'=>'double'
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }
}
