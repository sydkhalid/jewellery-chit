<?php

namespace App\Repositories;

use App\Models\ChitClosure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class MaturityClosingRepository
{
    public function query(): Builder
    {
        return ChitClosure::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['customer', 'enrollment.scheme', 'enrollment.branch', 'approver', 'creator'])
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->where('customer_id', $customerId))
            ->when(Arr::get($filters, 'enrollment_id'), fn (Builder $query, mixed $enrollmentId): Builder => $query->where('enrollment_id', $enrollmentId))
            ->when(Arr::get($filters, 'closure_type'), fn (Builder $query, string $type): Builder => $query->where('closure_type', $type))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('created_at', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('created_at', '<=', $toDate));
    }

    public function find(int $id): ?ChitClosure
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChitClosure
    {
        return ChitClosure::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ChitClosure $closure, array $data): ChitClosure
    {
        $closure->update($data);

        return $closure->refresh();
    }
}
