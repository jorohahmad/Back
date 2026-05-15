<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreNoticeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !==null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after_or_equal:today',
            'type' => ['required', Rule::in(['archive','project','personal','urgent'])],
        ];
    }

    #[Override]
    public function messages():array
    {
        return [
            'title.required' =>'the title is required',
            'type.in' =>'the type must be in archive, project,personal or urgent',
            'due_date.after_or_equal' => 'the date must be in the future'
        ];
    }
}
