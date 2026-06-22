<?php

namespace App\Domain\Comments\Events;

use App\Domain\Comments\Http\Resources\CommentResource;
use App\Domain\Comments\Models\Comment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched and broadcast when a reply to a root comment is posted.
 */
class ReplyCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Comment $reply) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("tasks.{$this->reply->task_id}.comments");
    }

    public function broadcastAs(): string
    {
        return 'reply.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new CommentResource(
            $this->reply->loadMissing('user')->loadCount('replies')
        ))->resolve();
    }
}
