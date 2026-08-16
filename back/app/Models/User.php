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
            'balance' => 'double', // لضمان التعامل معه كرقم عشري
        ];
    }
    // ارجاع المنتجات التي يملكها المستخدم
    public function products()
    {
        return $this->hasMany(Product::class, 'owner_id');
    }
    //ارجاع الطلبات التي كان فيها البائع
    public function sales()
    {
        return $this->hasMany(OrderItem::class, 'seller_id');
    }
    //ارجاع الطلبات التي كان فيها المشتري
    public function purchases()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }
    //  الإيجارات الصادرة (هو المؤجر/المالك)
    public function rentalsOut()
    {
        return $this->hasMany(RentalItem::class, 'lessor_id');
    }
    // الإيجارات الواردة (هو المستأجر)
    public function rentalsIn()
    {
        return $this->hasMany(Rental::class, 'renter_id');
    }
    // السلة
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }
    //الملاحظات للاادمن
    public function notices()
    {
        return $this->hasMany(Notice::class, 'user_id');
    }
    //
    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites', 'user_id', 'product_id')->withTimestamps();
    }
<<<<<<< HEAD
    public function isAdmin()
{
    // افترض أن لديك عمود اسمه 'role' وقيمته 'admin' للمدير
    return $this->role === 'admin'; 
    
    // أو إذا كان لديك عمود boolean اسمه 'is_admin'
    // return $this->is_admin == 1; 
}
=======
    //Rating
    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }
>>>>>>> 47d4fa70b2c4d136979e73c48a29781e37f02a5c
}
