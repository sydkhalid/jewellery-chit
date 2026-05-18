<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChitEnrollment extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'chit_no',
        'customer_id',
        'scheme_id',
        'branch_id',
        'assigned_staff_id',
        'start_date',
        'monthly_due_date',
        'maturity_date',
        'agreement_file',
        'remarks',
        'total_months',
        'monthly_amount',
        'total_payable',
        'total_paid',
        'total_pending',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'balance_amount',
        'maturity_status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'monthly_due_date' => 'integer',
            'maturity_date' => 'date',
            'total_months' => 'integer',
            'monthly_amount' => 'decimal:2',
            'total_payable' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'total_pending' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(ChitScheme::class, 'scheme_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(ChitInstallment::class, 'enrollment_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ChitPayment::class, 'enrollment_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ChitReceipt::class, 'enrollment_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(ChitLedger::class, 'enrollment_id');
    }

    public function closure(): HasOne
    {
        return $this->hasOne(ChitClosure::class, 'enrollment_id');
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(ChitCancellation::class, 'enrollment_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(ChitRefund::class, 'enrollment_id');
    }

    public function jewelleryInvoices(): HasMany
    {
        return $this->hasMany(JewelleryInvoice::class, 'enrollment_id');
    }

    public function scopeStaffWise(Builder $query, mixed $staffId): Builder
    {
        return $query->where('assigned_staff_id', $staffId);
    }

    protected function balanceAmount(): Attribute
    {
        return Attribute::get(fn (): float => round((float) $this->total_payable - (float) $this->total_paid, 2));
    }

    protected function maturityStatus(): Attribute
    {
        return Attribute::get(function (): string {
            if (in_array($this->status, ['closed', 'cancelled', 'defaulted'], true)) {
                return $this->status;
            }

            return $this->maturity_date && $this->maturity_date->isPast() ? 'matured' : 'running';
        });
    }
}
