<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'rater_id',
        'rated_id',
        'score',
        'comment',
    ];

    // علاقة التقييم بصاحبه
    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }
}