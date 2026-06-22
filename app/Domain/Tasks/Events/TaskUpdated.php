<?php

namespace App\Domain\Tasks\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TaskUpdated extends ShouldBeStored
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public array $changes,
    ) {}
}
