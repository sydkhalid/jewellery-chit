<?php

namespace App\Http\Requests;

use App\Support\SecureUpload;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MaturityClosingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('maturity.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'exists:chit_enrollments,id'],
            'closure_type' => ['required', 'in:normal,early,defaulted,cancelled'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'jewellery_adjustment_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_signature' => ['nullable', ...SecureUpload::image()],
            'remarks' => ['nullable', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $validator->errors(),
                ],
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
