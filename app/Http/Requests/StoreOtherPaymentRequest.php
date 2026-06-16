<?php

namespace App\Http\Requests;

use App\Models\Student;
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
        if (! $this->filled('student_id') && preg_match('/^([^-]+)-\s*([^-]+?)\s*-/', (string) $this->input('student_search'), $matches)) {
            $unit = trim($matches[1]);
            $studentId = Student::where('nis', trim($matches[2]))
                ->whereHas('schoolClass.educationUnit', fn ($query) => $query->where('code', $unit))
                ->value('id');
            $this->merge(['student_id' => $studentId]);
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', (string) $this->input('transaction_date'), $matches)) {
            $this->merge(['transaction_date' => "{$matches[3]}-{$matches[2]}-{$matches[1]}"]);
        }

        if (preg_match('/^([01]\d|2[0-3])[.:]([0-5]\d)$/', (string) $this->input('transaction_time'), $matches)) {
            $this->merge(['transaction_time' => "{$matches[1]}:{$matches[2]}:00"]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['required', 'date_format:H:i:s'],
            'student_id' => ['required', 'exists:students,id'],
            'fee_type_id' => ['required', 'exists:fee_types,id'],
            'payment_method' => ['required', Rule::in(['Cash', 'Transfer'])],
            'status' => ['required', Rule::in(['Diterima', 'Pending'])],
            'paid_amount' => ['required', 'integer', 'min:1'],
        ];

        if ($this->string('category')->value() === 'laundry') {
            $rules['year'] = ['required', 'integer', 'between:2000,2100'];
            $rules['months'] = ['required', 'array', 'min:1'];
            $rules['months.*'] = ['integer', 'between:1,12'];
        }

        return $rules;
    }
}
