<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Database\Eloquent\Builder;

class CustomerRepository
{
    public function query(): Builder
    {
        return Customer::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->with('nominee')
            ->withCount(['documents', 'enrollments'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status));
    }

    public function find(int|string $id): Customer
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->refresh();
    }

    public function delete(Customer $customer): bool
    {
        return (bool) $customer->delete();
    }

    public function deactivate(Customer $customer): Customer
    {
        $customer->update(['status' => 'inactive']);

        return $customer->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function uploadDocument(Customer $customer, array $data): CustomerDocument
    {
        return $customer->documents()->create($data);
    }
}
