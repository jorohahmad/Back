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
}
