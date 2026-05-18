<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ChitScheme;
use App\Repositories\ChitSchemeRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChitSchemeService
{
    public function __construct(
        private readonly ChitSchemeRepository $schemes
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createScheme(array $data): ChitScheme
    {
        return DB::transaction(function () use ($data): ChitScheme {
            $this->validateSchemeRules($data);

            $schemeData = $this->normalizeSchemeData($data);
            $schemeData['scheme_code'] = $this->generateSchemeCode();
            $schemeData['created_by'] = Auth::id();
            $schemeData['updated_by'] = Auth::id();

            $scheme = $this->schemes->create($schemeData);
            $this->logSchemeAction($scheme, 'create', 'created', null, $scheme->toArray());

            return $scheme;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateScheme(ChitScheme $scheme, array $data): ChitScheme
    {
        return DB::transaction(function () use ($scheme, $data): ChitScheme {
            $this->validateSchemeRules($data);

            $oldValues = $scheme->toArray();
            $schemeData = $this->normalizeSchemeData($data);
            $schemeData['updated_by'] = Auth::id();

            $scheme = $this->schemes->update($scheme, $schemeData);
            $this->logSchemeAction($scheme, 'update', 'updated', $oldValues, $scheme->toArray());

            return $scheme;
        });
    }

    public function deleteScheme(ChitScheme $scheme): bool
    {
        return DB::transaction(function () use ($scheme): bool {
            if ($scheme->enrollments()->where('status', 'active')->exists()) {
                throw ValidationException::withMessages([
                    'scheme' => 'Scheme has active enrollments. Mark the scheme inactive instead of deleting.',
                ]);
            }

            $oldValues = $scheme->toArray();
            $scheme->update(['deleted_by' => Auth::id()]);
            $deleted = $this->schemes->delete($scheme);

            $this->logSchemeAction($scheme, 'delete', 'deleted', $oldValues, null);

            return $deleted;
        });
    }

    public function changeSchemeStatus(ChitScheme $scheme, string $status): ChitScheme
    {
        return DB::transaction(function () use ($scheme, $status): ChitScheme {
            $oldValues = $scheme->toArray();
            $scheme = $this->schemes->update($scheme, [
                'status' => $status,
                'updated_by' => Auth::id(),
            ]);

            $this->logSchemeAction($scheme, 'status_change', 'status changed', $oldValues, $scheme->toArray());

            return $scheme;
        });
    }

    public function generateSchemeCode(): string
    {
        $nextId = (int) ChitScheme::withTrashed()->max('id') + 1;

        do {
            $code = 'SCH'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
            $nextId++;
        } while (ChitScheme::withTrashed()->where('scheme_code', $code)->exists());

        return $code;
    }

    public function calculateTotalPayable(ChitScheme $scheme): float
    {
        return match ($scheme->scheme_type) {
            'fixed_amount' => round((float) $scheme->monthly_amount * (int) $scheme->duration_months, 2),
            'flexible_amount' => round((float) $scheme->min_amount * (int) $scheme->duration_months, 2),
            default => 0.0,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateSchemeRules(array $data): void
    {
        $errors = [];

        if (($data['scheme_type'] ?? null) === 'fixed_amount' && ! filled($data['monthly_amount'] ?? null)) {
            $errors['monthly_amount'][] = 'Monthly amount is required for fixed amount schemes.';
        }

        if (($data['scheme_type'] ?? null) === 'gold_weight' && ! filled($data['gold_weight'] ?? null)) {
            $errors['gold_weight'][] = 'Gold weight is required for gold weight schemes.';
        }

        if (($data['scheme_type'] ?? null) === 'flexible_amount') {
            if (! filled($data['min_amount'] ?? null)) {
                $errors['min_amount'][] = 'Minimum amount is required for flexible amount schemes.';
            }

            if (! filled($data['max_amount'] ?? null)) {
                $errors['max_amount'][] = 'Maximum amount is required for flexible amount schemes.';
            }

            if (filled($data['min_amount'] ?? null) && filled($data['max_amount'] ?? null) && (float) $data['max_amount'] <= (float) $data['min_amount']) {
                $errors['max_amount'][] = 'Maximum amount must be greater than minimum amount.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSchemeData(array $data): array
    {
        $schemeData = Arr::only($data, [
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
        ]);

        $schemeData['monthly_amount'] = $schemeData['scheme_type'] === 'fixed_amount' ? $schemeData['monthly_amount'] : null;
        $schemeData['min_amount'] = $schemeData['scheme_type'] === 'flexible_amount' ? $schemeData['min_amount'] : null;
        $schemeData['max_amount'] = $schemeData['scheme_type'] === 'flexible_amount' ? $schemeData['max_amount'] : null;
        $schemeData['gold_weight'] = $schemeData['scheme_type'] === 'gold_weight' ? $schemeData['gold_weight'] : null;
        $schemeData['shop_bonus_value'] = ($schemeData['shop_bonus_type'] ?? 'none') === 'none' ? 0 : ($schemeData['shop_bonus_value'] ?? 0);
        $schemeData['late_fee_value'] = ($schemeData['late_fee_type'] ?? 'none') === 'none' ? 0 : ($schemeData['late_fee_value'] ?? 0);
        $schemeData['grace_period_days'] = $schemeData['grace_period_days'] ?? 0;

        return $schemeData;
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logSchemeAction(
        ChitScheme $scheme,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'chit_schemes',
            'description' => "Scheme {$scheme->scheme_code} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => ChitScheme::class,
            'auditable_id' => $scheme->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
