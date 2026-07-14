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
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', (string) $this->input('transaction_date'), $matches)) {
            $this->merge(['transaction_date' => "{$matches[3]}-{$matches[2]}-{$matches[1]}"]);
        }

        if (preg_match('/^([01]?\d|2[0-3])[.:]([0-5]\d)$/', (string) $this->input('transaction_time'), $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $this->merge(['transaction_time' => "{$hour}:{$matches[2]}:00"]);
        }
    }

    public function rules(): array
    {
        $cashOnly = $this->user()?->isPetugas() ?? false;

        return [
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i:s'],
            'student_id' => ['required', 'exists:students,id'],
            'month_count' => ['required_without:months', 'integer', 'min:1', 'max:120'],
            'months' => ['required_without:month_count', 'array', 'min:1'],
            'months.*' => ['integer', 'between:1,12'],
            'year' => ['required_with:months', 'integer', 'between:2000,2100'],
            'payment_method' => ['required', Rule::in($cashOnly ? ['Cash'] : ['Cash', 'Transfer'])],
            'status' => ['required', Rule::in($cashOnly ? ['Diterima'] : ['Diterima', 'Pending'])],
            'paid_amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
