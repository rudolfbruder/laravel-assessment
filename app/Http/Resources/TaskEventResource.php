<?php

namespace App\Http\Resources;

use App\Domain\Tasks\StoredEvents\TaskStoredEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskStoredEvent
 */
class TaskEventResource extends JsonResource
{
    /**
     * Transform a stored task event into a timeline entry.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = class_basename($this->event_class);

        return [
            'id' => $this->id,
            'event' => $event,
            'label' => $this->label($event),
            'changes' => $this->changesFor($event),
            'created_at' => $this->created_at,
        ];
    }

    private function label(string $event): string
    {
        return match ($event) {
            'TaskCreated' => 'Created',
            'TaskUpdated' => 'Updated',
            'TaskDeleted' => 'Deleted',
            default => $event,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function changesFor(string $event): ?array
    {
        return match ($event) {
            'TaskCreated' => $this->event_properties['attributes'] ?? null,
            'TaskUpdated' => $this->event_properties['changes'] ?? null,
            default => null,
        };
    }
}
