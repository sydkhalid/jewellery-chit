<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChitInstallment extends Model
{
    use HasCommonScopes, HasFactory;

    protected $fillable = [
        'enrollment_id',
        'installment_no',
        'due_date',
        'due_amount',
        'paid_amount',
        'balance_amount',
        'late_fee',
        'status',
        'paid_date',
    ];

    protected function casts(): array
    {
        return [
            'installment_no' => 'integer',
            'due_date' => 'date',
            'due_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'late_fee' => 'decimal:2',
            'paid_date' => 'date',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ChitEnrollment::class, 'enrollment_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ChitPayment::class, 'installment_id');
    }

    protected function balanceAmount(): Attribute
    {
        return Attribute::get(fn (mixed $value): float => round(
            $value !== null ? (float) $value : ((float) $this->due_amount + (float) $this->late_fee - (float) $this->paid_amount),
            2
        ));
    }
}
