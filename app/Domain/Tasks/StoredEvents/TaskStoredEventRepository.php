<?php

namespace App\Domain\Tasks\StoredEvents;

use Spatie\EventSourcing\StoredEvents\Repositories\EloquentStoredEventRepository;

class TaskStoredEventRepository extends EloquentStoredEventRepository
{
    public function __construct()
    {
        $this->storedEventModel = TaskStoredEvent::class;
    }
}
