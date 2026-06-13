<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOtherPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i:s'],
            'student_id' => ['required', 'exists:students,id'],
            'fee_type_id' => ['required', 'exists:fee_types,id'],
            'payment_method' => ['required', Rule::in(['Cash', 'Transfer'])],
            'status' => ['required', Rule::in(['Diterima', 'Pending'])],
            'paid_amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
