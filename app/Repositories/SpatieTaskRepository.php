<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SpatieTaskRepository implements TaskRepositoryInterface
{
    /**
     * Filter a user's tasks using spatie/laravel-query-builder.
     *
     * The normalized filter array is mapped onto the package's `filter[...]`
     * input so the public API stays identical to the classic engine.
     *
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return Collection<int, Task>
     */
    public function filterForUser(User $user, array $filters): Collection
    {
        $status = $filters['status'] ?? null;

        $request = new Request(['filter' => array_filter([
            'search' => $filters['search'] ?? null,
            'status' => $status === 'all' ? null : $status,
        ], fn ($value) => $value !== null && $value !== '')]);

        return QueryBuilder::for($user->tasks(), $request)
            ->allowedFilters(
                AllowedFilter::partial('search', 'name'),
                AllowedFilter::exact('status'),
            )
            ->latest()
            ->get();
    }
}
