<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nominee extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'relationship',
        'mobile',
        'address',
        'aadhaar_no',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
