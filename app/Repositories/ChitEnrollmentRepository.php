<?php

namespace App\Repositories;

use App\Models\ChitCancellation;
use App\Models\ChitEnrollment;
use Illuminate\Database\Eloquent\Builder;

class ChitEnrollmentRepository
{
    public function query(): Builder
    {
        return ChitEnrollment::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['customer', 'scheme', 'branch', 'assignedStaff'])
            ->withCount('payments')
            ->when($filters['customer_id'] ?? null, fn (Builder $query, mixed $id): Builder => $query->where('customer_id', $id))
            ->when($filters['scheme_id'] ?? null, fn (Builder $query, mixed $id): Builder => $query->where('scheme_id', $id))
            ->when($filters['branch_id'] ?? null, fn (Builder $query, mixed $id): Builder => $query->where('branch_id', $id))
            ->when($filters['assigned_staff_id'] ?? null, fn (Builder $query, mixed $id): Builder => $query->where('assigned_staff_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['from_date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '<=', $date));
    }

    public function find(int|string $id): ChitEnrollment
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChitEnrollment
    {
        return ChitEnrollment::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ChitEnrollment $enrollment, array $data): ChitEnrollment
    {
        $enrollment->update($data);

        return $enrollment->refresh();
    }

    public function delete(ChitEnrollment $enrollment): bool
    {
        return (bool) $enrollment->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancel(ChitEnrollment $enrollment, array $data): ChitCancellation
    {
        return $enrollment->cancellations()->create($data);
    }
}
