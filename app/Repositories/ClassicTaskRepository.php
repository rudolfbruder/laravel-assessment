<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ClassicTaskRepository implements TaskRepositoryInterface
{
    /**
     * Filter a user's tasks using hand-written queries via the Task model scopes.
     *
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return Collection<int, Task>
     */
    public function filterForUser(User $user, array $filters): Collection
    {
        return $user->tasks()
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->latest()
            ->get();
    }
}
