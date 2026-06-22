<?php

namespace App\Domain\Tasks\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TaskCreated extends ShouldBeStored
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public int $userId,
        public array $attributes,
    ) {}
}
