<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChitScheme extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'scheme_code',
        'name',
        'scheme_type',
        'monthly_amount',
        'min_amount',
        'max_amount',
        'gold_weight',
        'duration_months',
        'shop_bonus_type',
        'shop_bonus_value',
        'grace_period_days',
        'late_fee_type',
        'late_fee_value',
        'maturity_rule',
        'early_closing_rule',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'gold_weight' => 'decimal:3',
            'duration_months' => 'integer',
            'shop_bonus_value' => 'decimal:2',
            'grace_period_days' => 'integer',
            'late_fee_value' => 'decimal:2',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ChitEnrollment::class, 'scheme_id');
    }
}
