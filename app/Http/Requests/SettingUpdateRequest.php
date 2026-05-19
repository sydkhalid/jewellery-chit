<?php

namespace App\Http\Requests;

use App\Support\SecureUpload;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->hasAnyRole(['Admin', 'Manager']) ?? false)
            && ($this->user()?->can('settings.edit') ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_logo' => ['nullable', ...SecureUpload::image()],
            'shop_address' => ['nullable', 'string'],
            'shop_mobile' => ['nullable', 'string', 'max:20'],
            'shop_email' => ['nullable', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'receipt_prefix' => ['required', 'string', 'max:20'],
            'chit_number_prefix' => ['required', 'string', 'max:20'],
            'payment_number_prefix' => ['required', 'string', 'max:20'],
            'closure_number_prefix' => ['required', 'string', 'max:20'],
            'refund_number_prefix' => ['required', 'string', 'max:20'],
            'invoice_number_prefix' => ['required', 'string', 'max:20'],
            'handover_number_prefix' => ['required', 'string', 'max:20'],
            'financial_year' => ['required', 'string', 'max:20'],
            'terms_and_conditions' => ['nullable', 'string'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_api_url' => ['nullable', 'string', 'max:255'],
            'whatsapp_api_key' => ['nullable', 'string', 'max:500'],
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_api_url' => ['nullable', 'string', 'max:255'],
            'sms_api_key' => ['nullable', 'string', 'max:500'],
            'backup_enabled' => ['nullable', 'boolean'],
            'backup_disk' => ['nullable', 'string', 'max:100'],
            'default_grace_period_days' => ['nullable', 'integer', 'min:0'],
            'default_late_fee_type' => ['nullable', 'in:fixed,percentage,none'],
            'default_late_fee_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'whatsapp_enabled' => $this->boolean('whatsapp_enabled'),
            'sms_enabled' => $this->boolean('sms_enabled'),
            'backup_enabled' => $this->boolean('backup_enabled'),
        ]);
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
