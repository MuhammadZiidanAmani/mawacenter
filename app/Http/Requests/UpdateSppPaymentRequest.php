<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSppPaymentRequest extends FormRequest
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
            'student_id' => ['sometimes', 'required', 'exists:students,id'],
            'month_count' => ['sometimes', 'required', 'integer', 'min:1', 'max:120'],
            'payment_method' => ['required', Rule::in(['Cash', 'Transfer'])],
            'status' => ['required', Rule::in(['Diterima', 'Pending'])],
            'paid_amount' => ['sometimes', 'required', 'integer', 'min:0'],
            'transfer_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }
}
