<?php

namespace App\Http\Requests;

class JewelleryInvoiceUpdateRequest extends JewelleryInvoiceStoreRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('jewellery.edit') ?? false)
            && ((float) $this->input('chit_adjustment_amount', 0) <= 0 || ($user?->can('jewellery.adjust_chit') ?? false));
    }
}
