<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSppPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (preg_match('/^\d{2}:\d{2}$/', (string) $this->input('transaction_time'))) {
            $this->merge(['transaction_time' => $this->input('transaction_time').':00']);
        }
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i:s'],
            'student_id' => ['required', 'exists:students,id'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'payment_method' => ['required', Rule::in(['Cash', 'Transfer'])],
            'status' => ['required', Rule::in(['Diterima', 'Pending'])],
            'paid_amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
