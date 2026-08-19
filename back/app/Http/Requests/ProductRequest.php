<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'description' => 'required|string',
            'image1' => 'nullable|image|max:2048',
            'image2' => 'nullable|image|max:2048',
            'image3' => 'nullable|image|max:2048',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,mp4,aac,x-m4a',
            'video' => 'nullable|file|mimes:mp4,mov,avi,mkv|max:204800',
            'governorate' => 'required|string|max:100',
            'office' => 'required|string|max:100',
            'is_for_sale' => 'boolean',
            'sale_price' => 'required_if:is_for_sale,1|numeric|min:0',
            'is_for_rent' => 'boolean',
            'rent_price_daily' => 'required_if:is_for_rent,1|numeric|min:0',
            'count' => 'required|integer|min:1',
            'condition' => 'required|in:new,used',
            'announcement' => 'boolean',
            'repricing' => 'boolean'
        ];
    }
}
