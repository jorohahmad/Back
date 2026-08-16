<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'email'=>$this->email,
            'role'=>$this->role,
<<<<<<< HEAD
=======
            'rating' => round($this->received_ratings_avg_score ?? 0, 1),
>>>>>>> 47d4fa70b2c4d136979e73c48a29781e37f02a5c
            'image'=>asset('storage/' . $this->imagePersonal)
        ];
    }
    
}
