<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'name',
        'mobile',
        'alternate_mobile',
        'email',
        'photo',
        'aadhaar_no',
        'pan_no',
        'address',
        'city',
        'state',
        'pincode',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = [
        'full_address',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function nominee(): HasOne
    {
        return $this->hasOne(Nominee::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ChitEnrollment::class);
    }

    public function installments(): HasManyThrough
    {
        return $this->hasManyThrough(
            ChitInstallment::class,
            ChitEnrollment::class,
            'customer_id',
            'enrollment_id',
            'id',
            'id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ChitPayment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ChitReceipt::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(ChitLedger::class);
    }

    public function closures(): HasMany
    {
        return $this->hasMany(ChitClosure::class);
    }

    public function jewelleryInvoices(): HasMany
    {
        return $this->hasMany(JewelleryInvoice::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function whatsappLogs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::get(fn (): string => collect([
            $this->address,
            $this->city,
            $this->state,
            $this->pincode,
        ])->filter()->implode(', '));
    }
}
