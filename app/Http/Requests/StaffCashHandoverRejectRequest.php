<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StaffCashHandoverRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('staff_cash_handover.receive') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'remarks' => ['required', 'string'],
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
