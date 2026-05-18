<?php

namespace App\Repositories;

use App\Models\JewelleryInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class JewelleryInvoiceRepository
{
    public function query(): Builder
    {
        return JewelleryInvoice::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with(['customer', 'enrollment.scheme', 'creator', 'finalizer'])
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->where('customer_id', $customerId))
            ->when(Arr::get($filters, 'enrollment_id'), fn (Builder $query, mixed $enrollmentId): Builder => $query->where('enrollment_id', $enrollmentId))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('invoice_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('invoice_date', '<=', $toDate));
    }

    public function find(int $id): ?JewelleryInvoice
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): JewelleryInvoice
    {
        return JewelleryInvoice::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(JewelleryInvoice $invoice, array $data): JewelleryInvoice
    {
        $invoice->update($data);

        return $invoice->refresh();
    }
}
