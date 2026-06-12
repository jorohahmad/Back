<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class productAdmin extends JsonResource
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
            'category'=>$this->title,
            'price'=> $this->price,
            'audioUrl'=> $this->audio ? asset('storage/' . $this->audio) : null,
            'avatar' =>collect([$this->image1, $this->image2, $this->image3])
            ->filter()
            ->map(fn($img) => asset('storage/' . $img))
            ->values()
            ->toArray(),
            'videoUrl' =>$this->video ? asset('storage/' . $this->video) : null
        ];
    }
}
