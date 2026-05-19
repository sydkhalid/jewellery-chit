<?php

namespace App\Http\Requests;

use App\Support\SecureUpload;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChitEnrollmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'scheme_id' => ['required', 'exists:chit_schemes,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'assigned_staff_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'monthly_amount' => ['nullable', 'numeric', 'min:0'],
            'agreement_file' => ['nullable', ...SecureUpload::agreement()],
            'remarks' => ['nullable', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->is('api/*')) {
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
