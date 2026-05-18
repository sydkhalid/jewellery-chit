<?php

namespace App\Http\Requests;

class CashbookClosingBalanceRequest extends CashbookStoreRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'cashbook_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
