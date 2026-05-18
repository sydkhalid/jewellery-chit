<?php

namespace App\Repositories;

use App\Models\ChitEnrollment;
use App\Models\ChitLedger;
use App\Models\ChitPayment;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class LedgerRepository
{
    public function query(): Builder
    {
        return ChitLedger::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['customer', 'enrollment.scheme', 'enrollment.branch', 'enrollment.assignedStaff', 'creator'])
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->where('customer_id', $customerId))
            ->when(Arr::get($filters, 'enrollment_id'), fn (Builder $query, mixed $enrollmentId): Builder => $query->where('enrollment_id', $enrollmentId))
            ->when(Arr::get($filters, 'transaction_type'), fn (Builder $query, string $type): Builder => $query->where('transaction_type', $type))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('transaction_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('transaction_date', '<=', $toDate))
            ->when(Arr::get($filters, 'branch_id'), fn (Builder $query, mixed $branchId): Builder => $query->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('branch_id', $branchId)))
            ->when(Arr::get($filters, 'staff_id'), function (Builder $query, mixed $staffId): Builder {
                return $query->where(function (Builder $scoped) use ($staffId): void {
                    $scoped->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('assigned_staff_id', $staffId))
                        ->orWhere(function (Builder $paymentLedger) use ($staffId): void {
                            $paymentIds = ChitPayment::query()
                                ->where('staff_id', $staffId)
                                ->pluck('id');

                            $paymentLedger->where('reference_type', ChitPayment::class)
                                ->whereIn('reference_id', $paymentIds);
                        });
                });
            });
    }

    public function find(int $id): ?ChitLedger
    {
        return $this->query()->find($id);
    }

    public function getByCustomer(Customer $customer): Builder
    {
        return $this->query()
            ->with(['enrollment.scheme', 'creator'])
            ->where('customer_id', $customer->id)
            ->orderBy('transaction_date')
            ->orderBy('id');
    }

    public function getByEnrollment(ChitEnrollment $enrollment): Builder
    {
        return $this->query()
            ->with(['customer', 'creator'])
            ->where('enrollment_id', $enrollment->id)
            ->orderBy('transaction_date')
            ->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChitLedger
    {
        return ChitLedger::create($data);
    }

    public function getCurrentBalance(ChitEnrollment $enrollment): float
    {
        return (float) $this->query()
            ->where('enrollment_id', $enrollment->id)
            ->latest('transaction_date')
            ->latest('id')
            ->value('balance');
    }
}
