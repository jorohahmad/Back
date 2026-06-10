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
            'audio' => 'nullable|max:10240',
            'video' => 'nullable|max:51200',
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
    public function messages()
    {
        return [
            'title.required' => 'حقل العنوان مطلوب.',
            'description.required' => 'حقل الوصف مطلوب.',
            'sale_price.required_if' => 'حقل سعر البيع مطلوب عندما يكون المنتج للبيع.',
            'rent_price_daily.required_if' => 'حقل سعر الإيجار اليومي مطلوب عندما يكون المنتج للإيجار.',
            'count.required' => 'حقل الكمية مطلوب.',
            'count.integer' => 'حقل الكمية يجب أن يكون عدداً صحيحاً.',
            'count.min' => 'الكمية يجب أن تكون على الأقل 1.',
            'condition.required' => 'حقل الحالة مطلوب.',
            'condition.in' => 'حقل الحالة يجب أن يكون إما "new" أو "used".',
        ];
    }
}
