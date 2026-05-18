<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDocument extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'document_type',
        'document_number',
        'file_path',
        'status',
        'uploaded_by',
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
