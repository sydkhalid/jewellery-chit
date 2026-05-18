<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BranchStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('branch.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'branch_code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'branch_code')],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'status' => ['required', 'in:active,inactive'],
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
