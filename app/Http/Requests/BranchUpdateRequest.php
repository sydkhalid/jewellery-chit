<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class BranchUpdateRequest extends BranchStoreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('branch.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'name' => ['required', 'string', 'max:255'],
            'branch_code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'branch_code')->ignore($branch)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
