<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformEarning extends Model
{
    protected $fillable = [
        'order_id',
        'rental_id',
        'transaction_amount',
        'commission_amount',
        'type',
    ];
    // العلاقة مع طلب البيع
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // العلاقة مع عقد الإيجار
    public function rental()
    {
        return $this->belongsTo(Rental::class, 'rental_id');
    }
}
