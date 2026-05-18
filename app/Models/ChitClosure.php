<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChitClosure extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'closure_no',
        'enrollment_id',
        'customer_id',
        'closure_type',
        'total_paid',
        'shop_bonus',
        'deductions',
        'final_maturity_value',
        'refund_amount',
        'jewellery_adjustment_amount',
        'customer_signature',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_paid' => 'decimal:2',
            'shop_bonus' => 'decimal:2',
            'deductions' => 'decimal:2',
            'final_maturity_value' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'jewellery_adjustment_amount' => 'decimal:2',
            'approved_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
