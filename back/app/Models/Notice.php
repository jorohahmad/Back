<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Notice extends Model
{
    use HasFactory, Notifiable,HasApiTokens;

    protected $table = 'notices';

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'description',
        'type',
        'due_date'
    ];


    public function user() {
        return $this->belongsTo(User::class ,'user_id');
    }
    

    
}
