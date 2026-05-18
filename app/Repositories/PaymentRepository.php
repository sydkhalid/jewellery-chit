<?php

namespace App\Repositories;

use App\Models\ChitPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class PaymentRepository
{
    public function query(): Builder
    {
        return ChitPayment::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['customer', 'enrollment', 'paymentMode', 'branch', 'staff', 'receipt'])
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->where('customer_id', $customerId))
            ->when(Arr::get($filters, 'enrollment_id'), fn (Builder $query, mixed $enrollmentId): Builder => $query->where('enrollment_id', $enrollmentId))
            ->when(Arr::get($filters, 'payment_mode_id'), fn (Builder $query, mixed $modeId): Builder => $query->where('payment_mode_id', $modeId))
            ->when(Arr::get($filters, 'staff_id'), fn (Builder $query, mixed $staffId): Builder => $query->where('staff_id', $staffId))
            ->when(Arr::get($filters, 'branch_id'), fn (Builder $query, mixed $branchId): Builder => $query->where('branch_id', $branchId))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('payment_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('payment_date', '<=', $toDate));
    }

    public function find(int $id): ?ChitPayment
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChitPayment
    {
        return ChitPayment::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ChitPayment $payment, array $data): ChitPayment
    {
        $payment->update($data);

        return $payment->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancel(ChitPayment $payment, array $data): ChitPayment
    {
        $payment->update($data + ['status' => 'cancelled']);

        return $payment->refresh();
    }
}
