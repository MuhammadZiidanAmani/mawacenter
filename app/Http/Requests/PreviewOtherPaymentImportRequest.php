<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewOtherPaymentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required_without:token', 'file', 'mimes:xlsx', 'max:10240'],
            'token' => ['nullable', 'uuid'],
            'mappings' => ['nullable', 'array'],
            'mappings.*' => ['nullable', 'exists:fee_types,id'],
        ];
    }
}
