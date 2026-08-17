<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProductRentalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeItems = $this->items->where('status', 'active');
        return [
            'id' => $this->id,
            'owner_name' => $this->owner ? $this->owner->name : null,
            'owner_id' => $this->owner_id,
            'title' => $this->title,
            'description' => $this->description,
            'repricing'=>$this->repricing,
            'image' => collect([$this->image1, $this->image2, $this->image3])
                ->filter()
                ->map(fn($img) => asset('storage/' . $img))
                ->values()
                ->toArray(),
            'audio' => $this->audio ? asset('storage/' . $this->audio) : null,
            'video' => $this->video ? asset('storage/' . $this->video) : null,
            'is_for_rent' => $this->is_for_rent,
            'rent_price_daily' => $this->rent_price_daily,
            'is_favorite' => $this->favorites->isNotEmpty(),
            'items_count'      => $activeItems->count(),
            'serial_number'    => $activeItems->pluck('id')->toArray(),
        ];
    }
}
