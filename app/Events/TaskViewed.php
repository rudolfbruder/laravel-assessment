<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Simple sanity/presence event: broadcast when a user opens a task page,
 * so other viewers of the same task get notified live.
 */
class TaskViewed implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Task $task, public User $viewer) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("tasks.{$this->task->id}.comments");
    }

    public function broadcastAs(): string
    {
        return 'task.viewed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        info('som touch');

        return [
            'viewer_id' => $this->viewer->id,
            'viewer_name' => $this->viewer->name,
            'task_id' => $this->task->id,
        ];
    }
}
