<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'imagePersonal',
        'imageId',
        'role',
        'key',
        'active',
        'is_verified',
        'balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'double', 
        ];
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'owner_id');
    }
    public function sales()
    {
        return $this->hasMany(OrderItem::class, 'seller_id');
    }
    public function purchases()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }
    public function rentalsOut()
    {
        return $this->hasMany(RentalItem::class, 'lessor_id');
    }
    public function rentalsIn()
    {
        return $this->hasMany(Rental::class, 'renter_id');
    }
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }
    public function notices()
    {
        return $this->hasMany(Notice::class, 'user_id');
    }
    //
    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites', 'user_id', 'product_id')->withTimestamps();
    }
    //Rating
    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }
}
