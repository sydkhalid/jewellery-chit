<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasCommonScopes
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'overdue');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeBetweenDates(Builder $query, mixed $from, mixed $to, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [$from, $to]);
    }

    public function scopeBranchWise(Builder $query, mixed $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeStaffWise(Builder $query, mixed $staffId, string $column = 'staff_id'): Builder
    {
        return $query->where($column, $staffId);
    }
}
