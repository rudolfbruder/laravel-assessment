<?php

namespace App\Domain\Tasks\Aggregates;

use App\Domain\Tasks\Events\TaskCreated;
use App\Domain\Tasks\Events\TaskDeleted;
use App\Domain\Tasks\Events\TaskUpdated;
use App\Domain\Tasks\StoredEvents\TaskStoredEventRepository;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use Spatie\EventSourcing\StoredEvents\Repositories\StoredEventRepository;

class TaskAggregateRoot extends AggregateRoot
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createTask(int $userId, array $attributes): static
    {
        $this->recordThat(new TaskCreated($userId, $attributes));

        return $this;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function updateTask(array $changes): static
    {
        $this->recordThat(new TaskUpdated($changes));

        return $this;
    }

    public function deleteTask(): static
    {
        $this->recordThat(new TaskDeleted);

        return $this;
    }

    protected function getStoredEventRepository(): StoredEventRepository
    {
        return app(TaskStoredEventRepository::class);
    }
}
