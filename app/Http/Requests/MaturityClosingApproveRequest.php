<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaturityClosingApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('maturity.approve') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string'],
        ];
    }
}
