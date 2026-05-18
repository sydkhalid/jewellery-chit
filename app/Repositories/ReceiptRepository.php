<?php

namespace App\Repositories;

use App\Models\ChitReceipt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ReceiptRepository
{
    public function query(): Builder
    {
        return ChitReceipt::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['customer', 'enrollment.scheme', 'payment.paymentMode', 'payment.staff', 'payment.branch'])
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->where('customer_id', $customerId))
            ->when(Arr::get($filters, 'enrollment_id'), fn (Builder $query, mixed $enrollmentId): Builder => $query->where('enrollment_id', $enrollmentId))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('receipt_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('receipt_date', '<=', $toDate))
            ->when(Arr::get($filters, 'payment_mode_id'), fn (Builder $query, mixed $modeId): Builder => $query->whereHas('payment', fn (Builder $payment): Builder => $payment->where('payment_mode_id', $modeId)))
            ->when(Arr::get($filters, 'staff_id'), fn (Builder $query, mixed $staffId): Builder => $query->whereHas('payment', fn (Builder $payment): Builder => $payment->where('staff_id', $staffId)))
            ->when(Arr::get($filters, 'branch_id'), fn (Builder $query, mixed $branchId): Builder => $query->whereHas('payment', fn (Builder $payment): Builder => $payment->where('branch_id', $branchId)));
    }

    public function find(int $id): ?ChitReceipt
    {
        return $this->query()->find($id);
    }

    public function findByPayment(int $paymentId): ?ChitReceipt
    {
        return $this->query()->where('payment_id', $paymentId)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChitReceipt
    {
        return ChitReceipt::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ChitReceipt $receipt, array $data): ChitReceipt
    {
        $receipt->update($data);

        return $receipt->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancel(ChitReceipt $receipt, array $data): ChitReceipt
    {
        $receipt->update($data + ['status' => 'cancelled']);

        return $receipt->refresh();
    }
}
