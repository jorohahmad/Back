<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductRentalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_name' => $this->owner ? $this->owner->name : null,
            'owner_id' => $this->owner_id,
            'title' => $this->title,
            'description' => $this->description,
            'image1' => asset('storage/' . $this->image1),
            'image2' => asset('storage/' . $this->image2),
            'image3' => asset('storage/' . $this->image3),
            'audio' => asset('storage/' . $this->audio),
            'is_for_rent' => $this->is_for_rent,
            'rent_price_daily' => $this->rent_price_daily,
            'items_count' => $this->items()->count(),
        ];
    }
}
