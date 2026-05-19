<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\GoldRate;
use App\Repositories\GoldRateRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoldRateService
{
    public function __construct(
        private readonly GoldRateRepository $goldRates
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRate(array $data): GoldRate
    {
        return DB::transaction(function () use ($data): GoldRate {
            if (($data['status'] ?? null) === 'approved' || (bool) ($data['rate_locked'] ?? false)) {
                $this->assertCanApproveOrLock();
            }

            if ((bool) ($data['rate_locked'] ?? false) && ($data['status'] ?? 'pending') !== 'approved') {
                throw ValidationException::withMessages([
                    'rate_locked' => 'Only approved rates can be locked.',
                ]);
            }

            $rate = $this->goldRates->create([
                'rate_date' => $data['rate_date'],
                'gold_22k' => $data['gold_22k'],
                'gold_24k' => $data['gold_24k'],
                'silver_rate' => $data['silver_rate'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'rate_locked' => (bool) ($data['rate_locked'] ?? false),
                'created_by' => Auth::id(),
                'approved_by' => ($data['status'] ?? null) === 'approved' ? Auth::id() : null,
                'approved_at' => ($data['status'] ?? null) === 'approved' ? now() : null,
            ]);

            $rate->load(['creator', 'approver']);
            $this->logRateAction($rate, 'create', 'created', null, $rate->toArray());
            $this->forgetRateCache();

            return $rate;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRate(GoldRate $goldRate, array $data): GoldRate
    {
        return DB::transaction(function () use ($goldRate, $data): GoldRate {
            if ($goldRate->rate_locked) {
                throw ValidationException::withMessages([
                    'gold_rate' => 'Locked gold rates cannot be edited.',
                ]);
            }

            $oldValues = $goldRate->toArray();
            $status = $data['status'] ?? $goldRate->status;
            $rateLocked = (bool) ($data['rate_locked'] ?? $goldRate->rate_locked);

            if (($status !== $goldRate->status && in_array($status, ['approved', 'rejected'], true)) || ($rateLocked && ! $goldRate->rate_locked)) {
                $this->assertCanApproveOrLock();
            }

            if ($rateLocked && $status !== 'approved') {
                throw ValidationException::withMessages([
                    'rate_locked' => 'Only approved rates can be locked.',
                ]);
            }

            $goldRate = $this->goldRates->update($goldRate, [
                'rate_date' => $data['rate_date'],
                'gold_22k' => $data['gold_22k'],
                'gold_24k' => $data['gold_24k'],
                'silver_rate' => $data['silver_rate'] ?? null,
                'status' => $status,
                'rate_locked' => $rateLocked,
                'approved_by' => $status === 'approved' ? ($goldRate->approved_by ?: Auth::id()) : null,
                'approved_at' => $status === 'approved' ? ($goldRate->approved_at ?: now()) : null,
            ]);

            $goldRate->load(['creator', 'approver']);
            $this->logRateAction($goldRate, 'update', 'updated', $oldValues, $goldRate->toArray());
            $this->forgetRateCache();

            return $goldRate;
        });
    }

    public function approveRate(GoldRate $goldRate): GoldRate
    {
        return DB::transaction(function () use ($goldRate): GoldRate {
            $this->assertCanApproveOrLock();

            if ($goldRate->status === 'approved') {
                return $goldRate->load(['creator', 'approver']);
            }

            if ($goldRate->rate_locked) {
                throw ValidationException::withMessages([
                    'gold_rate' => 'Locked gold rates cannot be approved again.',
                ]);
            }

            $oldValues = $goldRate->toArray();
            $goldRate = $this->goldRates->update($goldRate, [
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $goldRate->load(['creator', 'approver']);
            $this->logRateAction($goldRate, 'approval', 'approved', $oldValues, $goldRate->toArray());
            $this->forgetRateCache();

            return $goldRate;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function rejectRate(GoldRate $goldRate, array $data): GoldRate
    {
        return DB::transaction(function () use ($goldRate, $data): GoldRate {
            $this->assertCanApproveOrLock();

            if ($goldRate->rate_locked) {
                throw ValidationException::withMessages([
                    'gold_rate' => 'Locked gold rates cannot be rejected.',
                ]);
            }

            $oldValues = $goldRate->toArray();
            $goldRate = $this->goldRates->update($goldRate, [
                'status' => 'rejected',
                'approved_by' => null,
                'approved_at' => null,
            ]);

            $goldRate->load(['creator', 'approver']);
            $this->logRateAction($goldRate, 'rejection', 'rejected', $oldValues, [
                'rate' => $goldRate->toArray(),
                'reason' => $data['reason'] ?? null,
            ]);
            $this->forgetRateCache();

            return $goldRate;
        });
    }

    public function lockRate(GoldRate $goldRate): GoldRate
    {
        return DB::transaction(function () use ($goldRate): GoldRate {
            $this->assertCanApproveOrLock();

            if ($goldRate->status !== 'approved') {
                throw ValidationException::withMessages([
                    'gold_rate' => 'Only approved rates can be locked.',
                ]);
            }

            $oldValues = $goldRate->toArray();
            $goldRate = $this->goldRates->update($goldRate, [
                'rate_locked' => true,
            ]);

            $goldRate->load(['creator', 'approver']);
            $this->logRateAction($goldRate, 'lock', 'locked', $oldValues, $goldRate->toArray());
            $this->forgetRateCache();

            return $goldRate;
        });
    }

    public function getTodayRate(): ?GoldRate
    {
        return Cache::remember(
            'gold-rates:today:'.today()->toDateString(),
            now()->addSeconds((int) config('jewellery.cache.gold_rate_ttl', 300)),
            fn (): ?GoldRate => GoldRate::query()
                ->whereDate('rate_date', today())
                ->where('status', 'approved')
                ->latest('id')
                ->first()
        );
    }

    public function getLatestApprovedRate(): ?GoldRate
    {
        return Cache::remember(
            'gold-rates:latest-approved',
            now()->addSeconds((int) config('jewellery.cache.gold_rate_ttl', 300)),
            fn (): ?GoldRate => GoldRate::query()
                ->where('status', 'approved')
                ->orderByDesc('rate_date')
                ->orderByDesc('id')
                ->first()
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getRateHistory(array $filters): Builder
    {
        return $this->goldRates->getForDataTable($filters)->orderByDesc('rate_date')->orderByDesc('id');
    }

    public function approvedBillingRate(): GoldRate
    {
        $rate = $this->getLatestApprovedRate();

        if (! $rate) {
            throw ValidationException::withMessages([
                'gold_rate' => 'An approved gold rate is required before creating jewellery invoices.',
            ]);
        }

        return $rate;
    }

    private function assertCanApproveOrLock(): void
    {
        if (! (Auth::user()?->hasAnyRole(['Admin', 'Manager']) ?? false)) {
            throw ValidationException::withMessages([
                'gold_rate' => 'Only Admin or Manager can approve, reject, or lock rates.',
            ]);
        }
    }

    private function forgetRateCache(): void
    {
        Cache::forget('gold-rates:today:'.today()->toDateString());
        Cache::forget('gold-rates:latest-approved');
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logRateAction(
        GoldRate $goldRate,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'gold_rates',
            'description' => "Gold rate {$goldRate->rate_date?->toDateString()} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => GoldRate::class,
            'auditable_id' => $goldRate->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
