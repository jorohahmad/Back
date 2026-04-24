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
    use HasFactory, Notifiable,HasApiTokens;

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
        'key'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime',
            // 'password' => 'hashed',
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
        return $this->hasMany(Order::class, 'seller_id');
    }
    //ارجاع الطلبات التي كان فيها المشتري
    public function purchases()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }
    //  الإيجارات الصادرة (هو المؤجر/المالك)
    public function rentalsOut()
    {
        return $this->hasMany(Rental::class, 'lessor_id');
    }
    // الإيجارات الواردة (هو المستأجر)
    public function rentalsIn()
    {
        return $this->hasMany(Rental::class, 'renter_id');
    }
}
