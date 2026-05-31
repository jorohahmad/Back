<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
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
            'product_id'      => 'required|exists:products,id',
            'type'            => 'required|in:sale,rent',
            'quantity'        => 'required|integer|min:1',
            'rent_start_date' => 'required_if:type,rent|date|after_or_equal:today',
            'rent_end_date'   => 'required_if:type,rent|date|after:rent_start_date',
        ];
    }
    public function messages()
    {
        return [
            'product_id.required' => 'Product ID is required.',
            'product_id.exists' => 'The selected product does not exist.',
            'type.required' => 'Type is required.',
            'type.in' => 'Type must be either "sale" or "rent".',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be an integer.',
            'quantity.min' => 'Quantity must be at least 1.',
            'rent_start_date.required_if' => 'Rent start date is required when type is rent.',
            'rent_start_date.date' => 'Rent start date must be a valid date.',
            'rent_start_date.after_or_equal' => 'Rent start date cannot be in the past.',
            'rent_end_date.required_if' => 'Rent end date is required when type is rent.',
            'rent_end_date.date' => 'Rent end date must be a valid date.',
            'rent_end_date.after' => 'Rent end date must be after the rent start date.',
        ];
    }
}
