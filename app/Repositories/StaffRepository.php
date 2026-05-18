<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class StaffRepository
{
    public function query(): Builder
    {
        return User::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['branch', 'roles'])
            ->withCount(['staffCollections', 'assignedChitEnrollments', 'staffCashHandovers'])
            ->role(['Admin', 'Manager', 'Staff'])
            ->when(Arr::get($filters, 'branch_id'), fn (Builder $query, mixed $branchId): Builder => $query->where('branch_id', $branchId))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'role'), fn (Builder $query, string $role): Builder => $query->role($role));
    }

    public function find(int $id): ?User
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $staff, array $data): User
    {
        $staff->update($data);

        return $staff->refresh();
    }

    public function delete(User $staff): bool
    {
        return (bool) $staff->delete();
    }
}
