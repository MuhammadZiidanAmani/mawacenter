<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['nullable', 'integer', 'min:1'],
            'academic_year_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_id.integer' => 'Unit pendidikan yang dipilih tidak valid.',
            'unit_id.min' => 'Unit pendidikan yang dipilih tidak valid.',
            'academic_year_id.integer' => 'Tahun pelajaran yang dipilih tidak valid.',
            'academic_year_id.min' => 'Tahun pelajaran yang dipilih tidak valid.',
        ];
    }
}
