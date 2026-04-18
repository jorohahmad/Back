<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    protected $table = 'rentals';
    protected $fillable = [
        'renter_id',
        'lessor_id',
        'product_item_id',
        'start_date',
        'end_date',
        'total_price',
        'status',
    ];
    public function renter() { return $this->belongsTo(User::class, 'renter_id'); }
    public function lessor() { return $this->belongsTo(User::class, 'lessor_id'); }
    
    public function item()
    {
        return $this->belongsTo(ProductItem::class, 'product_item_id');
    }
}
