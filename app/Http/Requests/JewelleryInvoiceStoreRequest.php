<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator as ValidationValidator;

class JewelleryInvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('jewellery.create') ?? false)
            && ((float) $this->input('chit_adjustment_amount', 0) <= 0 || ($user?->can('jewellery.adjust_chit') ?? false));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'enrollment_id' => ['nullable', 'exists:chit_enrollments,id'],
            'invoice_date' => ['required', 'date'],
            'gold_rate' => ['required', 'numeric', 'min:1'],
            'gross_weight' => ['required', 'numeric', 'min:0'],
            'net_weight' => ['required', 'numeric', 'min:0'],
            'making_charge' => ['nullable', 'numeric', 'min:0'],
            'wastage' => ['nullable', 'numeric', 'min:0'],
            'gst_amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'chit_adjustment_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.purity' => ['nullable', 'string', 'max:50'],
            'items.*.gross_weight' => ['required', 'numeric', 'min:0'],
            'items.*.net_weight' => ['required', 'numeric', 'min:0'],
            'items.*.rate' => ['required', 'numeric', 'min:1'],
            'items.*.making_charge' => ['nullable', 'numeric', 'min:0'],
            'items.*.wastage' => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (ValidationValidator $validator): void {
            if ((float) $this->input('gross_weight', 0) < (float) $this->input('net_weight', 0)) {
                $validator->errors()->add('gross_weight', 'Gross weight must be greater than or equal to net weight.');
            }

            foreach ($this->input('items', []) as $index => $item) {
                if ((float) ($item['gross_weight'] ?? 0) < (float) ($item['net_weight'] ?? 0)) {
                    $validator->errors()->add("items.{$index}.gross_weight", 'Item gross weight must be greater than or equal to net weight.');
                }
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
