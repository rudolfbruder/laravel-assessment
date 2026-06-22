<?php

namespace App\Domain\Comments\Events;

use App\Domain\Comments\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a root comment is posted on a task.
 *
 * Plain event for now; a later stage will implement ShouldBroadcast and
 * wire this to Laravel Reverb for real-time delivery.
 */
class CommentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Comment $comment) {}
}
