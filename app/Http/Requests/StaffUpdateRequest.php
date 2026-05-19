<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffUpdateRequest extends StaffStoreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('staff.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $staff = $this->route('staff');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff)],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($staff)],
            'password' => ['nullable', Password::defaults()],
            'branch_id' => ['required', 'exists:branches,id'],
            'role' => ['required', 'in:Admin,Manager,Staff'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
