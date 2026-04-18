<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    protected $table = 'product_items';
    protected $fillable = [
        'product_id',
        'serial_number',
        'condition',
        'status',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // تتبع العمليات التي تمت على هذه القطعة المحددة
    public function orders() { return $this->hasMany(Order::class); }
    public function rentals() { return $this->hasMany(Rental::class); }
}
