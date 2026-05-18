<?php

namespace App\Http\Requests;

class CashbookOpeningBalanceRequest extends CashbookStoreRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'cashbook_date' => ['required', 'date'],
            'credit' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
