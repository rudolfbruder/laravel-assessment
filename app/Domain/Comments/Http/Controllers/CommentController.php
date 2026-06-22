<?php

namespace App\Domain\Comments\Http\Controllers;

use App\Domain\Comments\Events\CommentCreated;
use App\Domain\Comments\Events\ReplyCreated;
use App\Domain\Comments\Http\Requests\StoreCommentRequest;
use App\Domain\Comments\Http\Resources\CommentResource;
use App\Domain\Comments\Models\Comment;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    private const ROOTS_PER_PAGE = 15;

    private const REPLIES_PREVIEW = 3;

    private const REPLIES_PER_PAGE = 10;

    /**
     * List a task's root comments with author, reply count, and a reply preview.
     */
    public function index(Task $task): AnonymousResourceCollection
    {
        $comments = $task->rootComments()
            ->with('user')
            ->withCount('replies')
            ->with(['replies' => fn ($query) => $query->with('user')->oldest()->limit(self::REPLIES_PREVIEW)])
            ->latest()
            ->paginate(self::ROOTS_PER_PAGE);

        return CommentResource::collection($comments);
    }

    /**
     * Post a comment or reply on a task, dispatching the matching event.
     */
    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $request->validated('parent_id'),
            'body' => $request->validated('body'),
        ]);

        $comment->load('user')->loadCount('replies');

        if ($comment->isRoot()) {
            CommentCreated::dispatch($comment);
        } else {
            ReplyCreated::dispatch($comment);
        }

        return CommentResource::make($comment)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Lazily fetch a root comment's replies, paginated.
     */
    public function replies(Comment $comment): AnonymousResourceCollection
    {
        $replies = $comment->replies()
            ->with('user')
            ->oldest()
            ->paginate(self::REPLIES_PER_PAGE);

        return CommentResource::collection($replies);
    }

    /**
     * Delete a comment (author only); replies cascade.
     */
    public function destroy(Comment $comment): Response
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
