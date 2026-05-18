<?php

namespace App\Repositories;

use App\Models\Cashbook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class CashbookRepository
{
    public function query(): Builder
    {
        return Cashbook::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['branch', 'paymentMode', 'creator'])
            ->when(Arr::get($filters, 'branch_id'), fn (Builder $query, mixed $branchId): Builder => $query->where('branch_id', $branchId))
            ->when(Arr::get($filters, 'transaction_type'), fn (Builder $query, string $type): Builder => $query->where('transaction_type', $type))
            ->when(Arr::get($filters, 'payment_mode_id'), fn (Builder $query, mixed $modeId): Builder => $query->where('payment_mode_id', $modeId))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('cashbook_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('cashbook_date', '<=', $toDate));
    }

    public function find(int $id): ?Cashbook
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Cashbook
    {
        return Cashbook::create($data);
    }
}
