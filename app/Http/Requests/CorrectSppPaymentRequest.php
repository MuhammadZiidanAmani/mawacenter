<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrectSppPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['sometimes', 'required', 'date'],
            'transaction_time' => ['sometimes', 'required', 'date_format:H:i:s'],
            'payment_method' => ['sometimes', 'required', Rule::in(['Cash', 'Transfer'])],
            'status' => ['sometimes', 'required', Rule::in(['Diterima', 'Pending'])],
            'new_paid_amount' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
            'return_url' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', (string) $this->input('transaction_date'), $matches)) {
            $this->merge(['transaction_date' => "{$matches[3]}-{$matches[2]}-{$matches[1]}"]);
        }

        if (preg_match('/^([01]\d|2[0-3])[.:]([0-5]\d)$/', (string) $this->input('transaction_time'), $matches)) {
            $this->merge(['transaction_time' => "{$matches[1]}:{$matches[2]}:00"]);
        }
    }
}
