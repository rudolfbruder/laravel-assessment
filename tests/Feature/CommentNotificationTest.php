<?php

namespace Tests\Feature;

use App\Domain\Comments\Events\CommentNotificationBroadcast;
use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Notifications\NewCommentNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_comment_notifies_all_other_users_but_not_the_author(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();
        $task = Task::factory()->create();
        Sanctum::actingAs($author);

        $this->postJson("/api/tasks/{$task->id}/comments", ['body' => 'Heads up'])->assertCreated();

        $this->assertSame(1, $other->fresh()->notifications()->count());
        $this->assertSame(1, $third->fresh()->notifications()->count());
        $this->assertSame(0, $author->fresh()->notifications()->count());

        $payload = $other->notifications()->first()->data;
        $this->assertSame($author->id, $payload['actor_id']);
        $this->assertSame($task->id, $payload['task_id']);
    }

    public function test_reply_does_not_notify_anyone(): void
    {
        $author = User::factory()->create();
        User::factory()->create();
        $task = Task::factory()->create();
        $root = Comment::factory()->create(['task_id' => $task->id]);
        Sanctum::actingAs($author);

        $this->postJson("/api/tasks/{$task->id}/comments", [
            'body' => 'a reply',
            'parent_id' => $root->id,
        ])->assertCreated();

        $this->assertSame(0, DatabaseNotification::count());
    }

    public function test_broadcast_event_channel_and_payload(): void
    {
        $task = Task::factory()->create();
        $comment = Comment::factory()->create(['task_id' => $task->id]);

        $event = new CommentNotificationBroadcast($comment);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertSame('private-comments.notifications', $event->broadcastOn()->name);
        $this->assertSame('comment.notification', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertSame($comment->id, $payload['comment_id']);
        $this->assertSame($task->id, $payload['task_id']);
        $this->assertArrayHasKey('actor_name', $payload);
    }

    public function test_notification_index_lists_with_unread_count(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();
        $user->notify(new NewCommentNotification($comment));
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_mark_one_and_all_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewCommentNotification(Comment::factory()->create()));
        $user->notify(new NewCommentNotification(Comment::factory()->create()));
        Sanctum::actingAs($user);

        $id = $user->notifications()->first()->id;
        $this->postJson("/api/notifications/{$id}/read")->assertNoContent();
        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        $this->postJson('/api/notifications/read-all')->assertNoContent();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_are_per_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $other->notify(new NewCommentNotification(Comment::factory()->create()));
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_notification_channel_authorizes_authenticated_user(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1.1',
            'channel_name' => 'private-comments.notifications',
        ])->assertOk();
    }

    public function test_notification_channel_rejects_guest(): void
    {
        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1.1',
            'channel_name' => 'private-comments.notifications',
        ])->assertUnauthorized();
    }
}
