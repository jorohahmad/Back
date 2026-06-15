<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCart extends FormRequest
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
            'cart_ids.*' => 'required|exists:carts,id',
            'action'     => 'required|in:increase,decrease',
            'steps'      => 'nullable|integer|min:1'
        ];
    }

    public function messages()
    {
        return [
            'cart_ids.required'   => 'يجب تحديد عنصر واحد على الأقل في السلة.',
            'cart_ids.array'      => 'صيغة المعرفات غير صحيحة.',
            'cart_ids.*.exists'   => 'أحد العناصر المحددة غير موجود في سلتك.',
            'action.required'     => 'يجب تحديد نوع العملية (زيادة أو نقصان).',
            'action.in'           => 'العملية المحددة غير صالحة.',
            'steps.min'           => 'عدد الخطوات يجب أن يكون 1 على الأقل.',
        ];
    }
}
