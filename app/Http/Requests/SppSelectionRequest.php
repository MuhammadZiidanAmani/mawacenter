<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SppSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['integer', 'between:1,12'],
        ];
    }
}
