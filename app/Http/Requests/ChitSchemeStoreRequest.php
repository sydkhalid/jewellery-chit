<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ChitSchemeStoreRequest extends FormRequest
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
        return $this->schemeRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function schemeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'scheme_type' => ['required', Rule::in(['fixed_amount', 'gold_weight', 'flexible_amount'])],
            'monthly_amount' => ['nullable', 'required_if:scheme_type,fixed_amount', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'required_if:scheme_type,flexible_amount', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'required_if:scheme_type,flexible_amount', 'numeric', 'gt:min_amount'],
            'gold_weight' => ['nullable', 'required_if:scheme_type,gold_weight', 'numeric', 'min:0'],
            'duration_months' => ['required', 'integer', 'min:1'],
            'shop_bonus_type' => ['required', Rule::in(['fixed', 'percentage', 'none'])],
            'shop_bonus_value' => ['nullable', 'numeric', 'min:0'],
            'grace_period_days' => ['nullable', 'integer', 'min:0'],
            'late_fee_type' => ['required', Rule::in(['fixed', 'percentage', 'none'])],
            'late_fee_value' => ['nullable', 'numeric', 'min:0'],
            'maturity_rule' => ['nullable', 'string'],
            'early_closing_rule' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monthly_amount.required_if' => 'Monthly amount is required for fixed amount schemes.',
            'min_amount.required_if' => 'Minimum amount is required for flexible amount schemes.',
            'max_amount.required_if' => 'Maximum amount is required for flexible amount schemes.',
            'max_amount.gt' => 'Maximum amount must be greater than minimum amount.',
            'gold_weight.required_if' => 'Gold weight is required for gold weight schemes.',
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
