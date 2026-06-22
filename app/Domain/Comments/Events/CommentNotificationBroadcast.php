<?php

namespace App\Domain\Comments\Events;

use App\Domain\Comments\Models\Comment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Live notification broadcast on a single shared channel when a root comment
 * is posted. Carries enough to render a toast; clients ignore their own action.
 */
class CommentNotificationBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Comment $comment) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('comments.notifications');
    }

    public function broadcastAs(): string
    {
        return 'comment.notification';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->comment->loadMissing(['user', 'task']);

        return [
            'actor_id' => $this->comment->user_id,
            'actor_name' => $this->comment->user?->name,
            'task_id' => $this->comment->task_id,
            'task_name' => $this->comment->task?->name,
            'comment_id' => $this->comment->id,
            'excerpt' => Str::limit($this->comment->body, 100),
            'created_at' => $this->comment->created_at,
        ];
    }
}
