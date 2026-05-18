<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JewelleryInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'item_name',
        'purity',
        'gross_weight',
        'net_weight',
        'rate',
        'making_charge',
        'wastage',
        'gst_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'gross_weight' => 'decimal:3',
            'net_weight' => 'decimal:3',
            'rate' => 'decimal:2',
            'making_charge' => 'decimal:2',
            'wastage' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(JewelleryInvoice::class, 'invoice_id');
    }
}
