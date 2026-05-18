<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_code',
        'name',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ChitEnrollment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ChitPayment::class);
    }

    public function cashbooks(): HasMany
    {
        return $this->hasMany(Cashbook::class);
    }

    public function staffCashHandovers(): HasMany
    {
        return $this->hasMany(StaffCashHandover::class);
    }
}
