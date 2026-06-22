<?php

namespace App\Domain\Comments\Policies;

use App\Domain\Comments\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Only the author may delete their comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
