<?php

namespace App\Repositories;

use App\Models\ChitScheme;
use Illuminate\Database\Eloquent\Builder;

class ChitSchemeRepository
{
    public function query(): Builder
    {
        return ChitScheme::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getForDataTable(array $filters = []): Builder
    {
        return $this->query()
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => fn (Builder $query): Builder => $query->where('status', 'active'),
            ])
            ->when($filters['scheme_type'] ?? null, fn (Builder $query, string $type): Builder => $query->where('scheme_type', $type))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status));
    }

    public function find(int|string $id): ChitScheme
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChitScheme
    {
        return ChitScheme::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ChitScheme $scheme, array $data): ChitScheme
    {
        $scheme->update($data);

        return $scheme->refresh();
    }

    public function delete(ChitScheme $scheme): bool
    {
        return (bool) $scheme->delete();
    }

    public function changeStatus(ChitScheme $scheme, string $status): ChitScheme
    {
        $scheme->update(['status' => $status]);

        return $scheme->refresh();
    }
}
