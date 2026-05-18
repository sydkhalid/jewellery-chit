<?php

namespace App\Http\Requests;

use App\Services\CashflowService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CashbookStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cashflow.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'cashbook_date' => ['required', 'date'],
            'transaction_type' => ['required', 'in:'.implode(',', CashflowService::TRANSACTION_TYPES)],
            'payment_mode_id' => ['nullable', 'exists:payment_modes,id'],
            'debit' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['nullable', 'numeric', 'min:0'],
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
