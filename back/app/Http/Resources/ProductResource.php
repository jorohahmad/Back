<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'image'            => $this->image1 ? asset('storage/' . $this->image1) : null,

            // خصائص البيع
            'is_for_sale'      => (bool) $this->is_for_sale,
            'sale_price'       => $this->is_for_sale ? $this->sale_price : null,
            'stock'            => $this->stock,

            // خصائص الإيجار
            'is_for_rent'      => (bool) $this->is_for_rent,
            'rent_price_daily' => $this->is_for_rent ? $this->rent_price_daily : null,

            // إذا كنت تريد إرجاع حالة المفضلة للمستخدم الحالي (بما أننا قمنا بتحميلها في الـ Service)
            'is_favorite'      => $this->favorites && $this->favorites->isNotEmpty(),

            'governorate' => $this->governorate,
            'office'      => $this->office
        ];
    }
}
