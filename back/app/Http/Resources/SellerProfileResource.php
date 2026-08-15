<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
   public function toArray(Request $request): array
    {
        return [
            'seller_info' => new UsersResource($this), // معلومات البائع مع التقييم
            
            // إرجاع جميع المنتجات في مصفوفة واحدة موحدة
            'products' => ProductResource::collection($this->products),
        ];
    }
}
