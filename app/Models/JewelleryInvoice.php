<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JewelleryInvoice extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'customer_id',
        'enrollment_id',
        'invoice_date',
        'gold_rate',
        'gross_weight',
        'net_weight',
        'making_charge',
        'wastage',
        'gst_amount',
        'discount',
        'chit_adjustment_amount',
        'total_amount',
        'balance_payable',
        'status',
        'created_by',
        'finalized_by',
        'finalized_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'gold_rate' => 'decimal:2',
            'gross_weight' => 'decimal:3',
            'net_weight' => 'decimal:3',
            'making_charge' => 'decimal:2',
            'wastage' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'chit_adjustment_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'balance_payable' => 'decimal:2',
            'finalized_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ChitEnrollment::class, 'enrollment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(JewelleryInvoiceItem::class, 'invoice_id');
    }
}
