<?php

namespace App\Domain\Comments\Events;

use App\Domain\Comments\Http\Resources\CommentResource;
use App\Domain\Comments\Models\Comment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched and broadcast when a root comment is posted on a task.
 */
class CommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Comment $comment) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("tasks.{$this->comment->task_id}.comments");
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new CommentResource(
            $this->comment->loadMissing('user')->loadCount('replies')
        ))->resolve();
    }
}
