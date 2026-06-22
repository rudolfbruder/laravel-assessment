<?php

namespace App\Domain\Comments\Listeners;

use App\Domain\Comments\Events\CommentCreated;
use App\Domain\Comments\Events\CommentNotificationBroadcast;
use App\Domain\Comments\Notifications\NewCommentNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * On a new root comment, persist a database notification for every other user
 * and broadcast a single live notification on the shared channel.
 */
class NotifyUsersOfComment
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment->loadMissing(['user', 'task']);

        User::where('id', '!=', $comment->user_id)
            ->chunkById(200, function ($users) use ($comment): void {
                Notification::send($users, new NewCommentNotification($comment));
            });

        broadcast(new CommentNotificationBroadcast($comment));
    }
}
