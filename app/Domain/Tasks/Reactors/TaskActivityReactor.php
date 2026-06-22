<?php

namespace App\Domain\Tasks\Reactors;

use App\Domain\Tasks\Events\TaskCreated;
use App\Domain\Tasks\Events\TaskDeleted;
use App\Domain\Tasks\Events\TaskUpdated;
use Illuminate\Support\Facades\Log;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

/**
 * Records task lifecycle activity to the application log.
 *
 * Logging is a side effect (not replayed), so it lives in a reactor rather
 * than the projector.
 */
class TaskActivityReactor extends Reactor
{
    public function onTaskCreated(TaskCreated $event, string $aggregateUuid): void
    {
        Log::info('Task created', [
            'task_uuid' => $aggregateUuid,
            'user_id' => $event->userId,
        ]);
    }

    public function onTaskUpdated(TaskUpdated $event, string $aggregateUuid): void
    {
        Log::info('Task updated', [
            'task_uuid' => $aggregateUuid,
            'changed' => array_keys($event->changes),
        ]);
    }

    public function onTaskDeleted(TaskDeleted $event, string $aggregateUuid): void
    {
        Log::info('Task deleted', [
            'task_uuid' => $aggregateUuid,
        ]);
    }
}
