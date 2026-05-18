<?php

namespace App\Repositories;

use App\Models\StaffCashHandover;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class StaffCashHandoverRepository
{
    public function query(): Builder
    {
        return StaffCashHandover::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['staff', 'branch', 'receiver'])
            ->when(Arr::get($filters, 'staff_id'), fn (Builder $query, mixed $staffId): Builder => $query->where('staff_id', $staffId))
            ->when(Arr::get($filters, 'branch_id'), fn (Builder $query, mixed $branchId): Builder => $query->where('branch_id', $branchId))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('handover_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('handover_date', '<=', $toDate));
    }

    public function find(int $id): ?StaffCashHandover
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StaffCashHandover
    {
        return StaffCashHandover::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StaffCashHandover $handover, array $data): StaffCashHandover
    {
        $handover->update($data);

        return $handover->refresh();
    }
}
