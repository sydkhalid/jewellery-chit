<?php

namespace App\Repositories;

use App\Models\GoldRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class GoldRateRepository
{
    public function query(): Builder
    {
        return GoldRate::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['creator', 'approver'])
            ->when(Arr::get($filters, 'rate_date'), fn (Builder $query, string $date): Builder => $query->whereDate('rate_date', $date))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::has($filters, 'rate_locked') && Arr::get($filters, 'rate_locked') !== '', fn (Builder $query): Builder => $query->where('rate_locked', (bool) Arr::get($filters, 'rate_locked')))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('rate_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('rate_date', '<=', $toDate));
    }

    public function find(int $id): ?GoldRate
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GoldRate
    {
        return GoldRate::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GoldRate $goldRate, array $data): GoldRate
    {
        $goldRate->update($data);

        return $goldRate->refresh();
    }
}
