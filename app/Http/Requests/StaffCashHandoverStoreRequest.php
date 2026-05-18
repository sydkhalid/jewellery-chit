<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StaffCashHandoverStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('staff_cash_handover.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'handover_date' => ['required', 'date'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'upi_amount' => ['nullable', 'numeric', 'min:0'],
            'card_amount' => ['nullable', 'numeric', 'min:0'],
            'bank_amount' => ['nullable', 'numeric', 'min:0'],
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
