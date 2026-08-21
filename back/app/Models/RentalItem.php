<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalItem extends Model
{
    protected $fillable = [
        'rental_id',
        'lessor_id',
        'status',
        'product_item_id',
        'start_date',
        'end_date',
    ];
    public function productItem()
    {
        return $this->belongsTo(ProductItem::class, 'product_item_id');
    }
    public function rental()
    {
        return $this->belongsTo(Rental::class, 'rental_id');
    }
    public function lessor()
    {
        return $this->belongsTo(User::class, 'lessor_id');
    }
}
