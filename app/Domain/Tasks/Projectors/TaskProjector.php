<?php

namespace App\Domain\Tasks\Projectors;

use App\Domain\Tasks\Events\TaskCreated;
use App\Domain\Tasks\Events\TaskDeleted;
use App\Domain\Tasks\Events\TaskUpdated;
use App\Models\Task;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class TaskProjector extends Projector
{
    public function onTaskCreated(TaskCreated $event, string $aggregateUuid): void
    {
        Task::updateOrCreate(
            ['uuid' => $aggregateUuid],
            [...$event->attributes, 'user_id' => $event->userId],
        );
    }

    public function onTaskUpdated(TaskUpdated $event, string $aggregateUuid): void
    {
        Task::query()
            ->where('uuid', $aggregateUuid)
            ->update($event->changes);
    }

    public function onTaskDeleted(TaskDeleted $event, string $aggregateUuid): void
    {
        Task::query()
            ->where('uuid', $aggregateUuid)
            ->delete();
    }
}
