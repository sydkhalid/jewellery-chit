<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GoldRateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gold_rates.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rate_date' => ['required', 'date'],
            'gold_22k' => ['required', 'numeric', 'min:1'],
            'gold_24k' => ['required', 'numeric', 'min:1'],
            'silver_rate' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'rate_locked' => ['nullable', 'boolean'],
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
