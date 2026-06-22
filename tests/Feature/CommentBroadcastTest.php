<?php

namespace Tests\Feature;

use App\Domain\Comments\Events\CommentCreated;
use App\Domain\Comments\Events\ReplyCreated;
use App\Domain\Comments\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_created_is_broadcastable_on_the_task_channel_with_payload(): void
    {
        $task = Task::factory()->create();
        $comment = Comment::factory()->create(['task_id' => $task->id]);

        $event = new CommentCreated($comment);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertSame("private-tasks.{$task->id}.comments", $event->broadcastOn()->name);
        $this->assertSame('comment.created', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertSame($comment->id, $payload['id']);
        $this->assertNull($payload['parent_id']);
        $this->assertArrayHasKey('author', $payload);
        $this->assertSame($comment->user->id, $payload['author']['id']);
        $this->assertArrayNotHasKey('email', $payload['author']);
    }

    public function test_reply_created_broadcasts_on_parent_task_channel_with_parent_id(): void
    {
        $task = Task::factory()->create();
        $root = Comment::factory()->create(['task_id' => $task->id]);
        $reply = Comment::factory()->reply($root)->create();

        $event = new ReplyCreated($reply);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertSame("private-tasks.{$task->id}.comments", $event->broadcastOn()->name);
        $this->assertSame('reply.created', $event->broadcastAs());
        $this->assertSame($root->id, $event->broadcastWith()['parent_id']);
    }

    public function test_channel_authorizes_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $task = Task::factory()->create();

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-tasks.{$task->id}.comments",
        ])->assertOk();
    }

    public function test_channel_rejects_unauthenticated_subscriber(): void
    {
        $task = Task::factory()->create();

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-tasks.{$task->id}.comments",
        ])->assertUnauthorized();
    }
}
