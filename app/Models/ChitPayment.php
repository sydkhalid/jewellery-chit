<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChitPayment extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_no',
        'enrollment_id',
        'customer_id',
        'installment_id',
        'payment_mode_id',
        'branch_id',
        'staff_id',
        'payment_date',
        'amount',
        'late_fee_amount',
        'total_amount',
        'transaction_id',
        'remarks',
        'payment_type',
        'status',
        'edit_status',
        'edit_payload',
        'edit_requested_by',
        'edit_requested_at',
        'edit_approved_by',
        'edit_approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
    ];

    protected $appends = [
        'formatted_payment_no',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'edit_payload' => 'array',
            'edit_requested_at' => 'datetime',
            'edit_approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ChitEnrollment::class, 'enrollment_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(ChitInstallment::class, 'installment_id');
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edit_requested_by');
    }

    public function editApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edit_approved_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(ChitReceipt::class, 'payment_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ChitPaymentAllocation::class, 'payment_id');
    }

    protected function formattedPaymentNo(): Attribute
    {
        return Attribute::get(fn (): string => strtoupper($this->payment_no));
    }
}
