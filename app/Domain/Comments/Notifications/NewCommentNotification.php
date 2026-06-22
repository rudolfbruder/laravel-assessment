<?php

namespace App\Domain\Comments\Notifications;

use App\Domain\Comments\Models\Comment;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewCommentNotification extends Notification
{
    public function __construct(public Comment $comment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'actor_id' => $this->comment->user_id,
            'actor_name' => $this->comment->user?->name,
            'task_id' => $this->comment->task_id,
            'task_name' => $this->comment->task?->name,
            'comment_id' => $this->comment->id,
            'excerpt' => Str::limit($this->comment->body, 100),
        ];
    }
}
