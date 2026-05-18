<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChitReceipt extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_no',
        'payment_id',
        'enrollment_id',
        'customer_id',
        'receipt_date',
        'amount',
        'pdf_path',
        'print_count',
        'status',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $appends = [
        'formatted_receipt_no',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'amount' => 'decimal:2',
            'print_count' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ChitPayment::class, 'payment_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ChitEnrollment::class, 'enrollment_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function formattedReceiptNo(): Attribute
    {
        return Attribute::get(fn (): string => strtoupper($this->receipt_no));
    }
}
