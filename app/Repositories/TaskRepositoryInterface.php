<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    /**
     * Return the user's tasks, newest first, after applying the given filters.
     *
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return Collection<int, Task>
     */
    public function filterForUser(User $user, array $filters): Collection;
}
