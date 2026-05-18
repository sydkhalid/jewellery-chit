<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class BranchRepository
{
    public function query(): Builder
    {
        return Branch::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->withCount(['users', 'enrollments', 'payments'])
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'city'), fn (Builder $query, string $city): Builder => $query->where('city', 'like', "%{$city}%"));
    }

    public function find(int $id): ?Branch
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch->refresh();
    }

    public function delete(Branch $branch): bool
    {
        return (bool) $branch->delete();
    }
}
