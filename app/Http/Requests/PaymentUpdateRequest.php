<?php

namespace App\Http\Requests;

use App\Models\PaymentMode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'exists:chit_enrollments,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'installment_id' => ['nullable', 'exists:chit_installments,id'],
            'payment_mode_id' => ['required', 'exists:payment_modes,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'staff_id' => ['nullable', 'exists:users,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'late_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
            'payment_type' => ['required', 'in:full,partial,advance,multiple_month'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mode = PaymentMode::find($this->input('payment_mode_id'));

            if ($mode && $mode->code !== 'cash' && blank($this->input('transaction_id'))) {
                $validator->errors()->add('transaction_id', 'Transaction ID is required for '.$mode->name.' payments.');
            }
        });
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
