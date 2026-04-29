<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $fillable = [
        'buyer_id',
        'seller_id',
        'product_item_id',
        'total_price',
        'status',
    ];
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function item()
    {
        return $this->belongsTo(ProductItem::class, 'product_item_id');
    }
    // داخل كلاس Order
    public function platformEarning()
    {
        // علاقة واحد لواحد (كل طلب له سجل ربح واحد)
        return $this->hasOne(PlatformEarning::class, 'order_id');
    }
}
