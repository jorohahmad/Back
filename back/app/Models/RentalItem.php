<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalItem extends Model
{
    protected $fillable = [
        'rental_id',
        'lessor_id',
        'product_item_id',
        'start_date',
        'end_date',
    ];
}
