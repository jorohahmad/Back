<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    protected $table = 'rentals';
    protected $fillable = [
        'renter_id',
        // 'lessor_id',
        // 'product_item_id',
        'start_date',
        'end_date',
        'total_price',
        'status',
        'transaction_id',
        'transfer_status',
        'expected_arrival_at',
        'total_insurance',
        'insurance_status',
        'receive_governorate',
        'receive_office'
    ];
    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }
    // public function lessor()
    // {
    //     return $this->belongsTo(User::class, 'lessor_id');
    // }

    // public function item()
    // {
    //     return $this->belongsTo(ProductItem::class, 'product_item_id');
    // }
    public function platformEarning()
    {
        return $this->hasOne(PlatformEarning::class, 'rental_id');
    }

    public function rentalItems()
    {
        return $this->hasMany(RentalItem::class, 'rental_id');
    }
}
