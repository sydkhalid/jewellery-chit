<?php

namespace App\Repositories;

use App\Models\ChitInstallment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class InstallmentRepository
{
    public function query(): Builder
    {
        return ChitInstallment::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['enrollment.customer', 'enrollment.scheme', 'enrollment.branch', 'enrollment.assignedStaff'])
            ->when(Arr::get($filters, 'customer_id'), function (Builder $query, mixed $customerId): void {
                $query->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('customer_id', $customerId));
            })
            ->when(Arr::get($filters, 'enrollment_id'), fn (Builder $query, mixed $enrollmentId): Builder => $query->where('enrollment_id', $enrollmentId))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'assigned_staff_id'), function (Builder $query, mixed $staffId): void {
                $query->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('assigned_staff_id', $staffId));
            })
            ->when(Arr::get($filters, 'branch_id'), function (Builder $query, mixed $branchId): void {
                $query->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('branch_id', $branchId));
            })
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('due_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('due_date', '<=', $toDate));
    }

    public function find(int $id): ?ChitInstallment
    {
        return $this->query()->find($id);
    }

    public function getByEnrollment(int $enrollmentId): Builder
    {
        return $this->query()
            ->where('enrollment_id', $enrollmentId)
            ->orderBy('installment_no');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChitInstallment
    {
        return ChitInstallment::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ChitInstallment $installment, array $data): ChitInstallment
    {
        $installment->update($data);

        return $installment->refresh();
    }

    public function updateStatus(ChitInstallment $installment, string $status): ChitInstallment
    {
        $installment->update(['status' => $status]);

        return $installment->refresh();
    }
}
