<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class deleteCartRequest extends FormRequest
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
            'cart_ids'   => 'required|array|min:1',
            'cart_ids.*' => 'required|integer|exists:carts,id',
        ];
    }

    public function messages()
    {
        return [
            'cart_ids.required' => 'Cart IDs are required.',
            'cart_ids.array' => 'Cart IDs must be an array.',
            'cart_ids.min' => 'At least one Cart ID must be provided.',
            'cart_ids.*.required' => 'Each Cart ID is required.',
            'cart_ids.*.integer' => 'Each Cart ID must be an integer.',
            'cart_ids.*.exists' => 'One or more of the specified Cart IDs do not exist.',
        ];
    }
}
