<?php

namespace App\Domain\Tasks\StoredEvents;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class TaskStoredEvent extends EloquentStoredEvent
{
    protected $table = 'task_stored_events';
}
