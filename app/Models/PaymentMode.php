<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMode extends Model
{
    use HasCommonScopes, HasFactory;

    protected $fillable = [
        'name',
        'code',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ChitPayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(ChitRefund::class);
    }

    public function cashbooks(): HasMany
    {
        return $this->hasMany(Cashbook::class);
    }
}
