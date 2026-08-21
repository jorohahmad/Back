<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProductSaleResource extends JsonResource
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
            'repricing' => $this->repricing,
            'image' => collect([$this->image1, $this->image2, $this->image3])
                ->filter(fn($img) => !is_null($img) && $img !== '')
                ->map(fn($img) => asset('storage/' . $img))
                ->values()
                ->toArray(),

            'audio' => $this->audio ? asset('storage/' . $this->audio) : null,
            'video' => $this->video ? asset('storage/' . $this->video) : null,
            'is_for_sale' => $this->is_for_sale,
            'sale_price' => $this->sale_price,
            'is_favorite' => $this->favorites->isNotEmpty(),
            'items_count' => $this->stock,
            'governorate' => $this->governorate,
            'office'      => $this->office,
            'condition'   => $this->condition,

        ];
    }
}
