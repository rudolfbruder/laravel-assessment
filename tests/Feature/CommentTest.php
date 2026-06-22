<?php

namespace Tests\Feature;

use App\Domain\Comments\Events\CommentCreated;
use App\Domain\Comments\Events\ReplyCreated;
use App\Domain\Comments\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_authenticated_user_can_post_a_comment(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();

        $response = $this->postJson("/api/tasks/{$task->id}/comments", ['body' => 'Looks good']);

        $response->assertCreated()
            ->assertJsonPath('data.body', 'Looks good')
            ->assertJsonPath('data.author.id', $user->id);

        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'parent_id' => null,
            'body' => 'Looks good',
        ]);
    }

    public function test_any_authenticated_user_can_comment_on_a_task_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $task = Task::factory()->for($owner)->create();
        $this->actingUser(); // a different, non-owner user

        $this->postJson("/api/tasks/{$task->id}/comments", ['body' => 'PM note'])
            ->assertCreated();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $task = Task::factory()->create();

        $this->postJson("/api/tasks/{$task->id}/comments", ['body' => 'hi'])
            ->assertUnauthorized();
    }

    public function test_body_is_required(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();

        $this->postJson("/api/tasks/{$task->id}/comments", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_whitespace_only_body_is_rejected(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();

        $this->postJson("/api/tasks/{$task->id}/comments", ['body' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_over_length_body_is_rejected(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();

        $this->postJson("/api/tasks/{$task->id}/comments", ['body' => str_repeat('a', 2001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_reply_to_root_comment_succeeds(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();
        $root = Comment::factory()->create(['task_id' => $task->id]);

        $response = $this->postJson("/api/tasks/{$task->id}/comments", [
            'body' => 'Agreed',
            'parent_id' => $root->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.parent_id', $root->id);
        $this->assertDatabaseHas('comments', ['parent_id' => $root->id, 'body' => 'Agreed']);
    }

    public function test_reply_to_a_reply_is_rejected(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();
        $root = Comment::factory()->create(['task_id' => $task->id]);
        $reply = Comment::factory()->reply($root)->create();

        $this->postJson("/api/tasks/{$task->id}/comments", [
            'body' => 'nested',
            'parent_id' => $reply->id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_reply_parent_must_belong_to_same_task(): void
    {
        $user = $this->actingUser();
        $taskA = Task::factory()->for($user)->create();
        $taskB = Task::factory()->for($user)->create();
        $rootOnB = Comment::factory()->create(['task_id' => $taskB->id]);

        $this->postJson("/api/tasks/{$taskA->id}/comments", [
            'body' => 'wrong task',
            'parent_id' => $rootOnB->id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_index_returns_roots_with_author_count_and_reply_preview(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();
        $root = Comment::factory()->create(['task_id' => $task->id]);
        Comment::factory()->count(5)->reply($root)->create();

        $response = $this->getJson("/api/tasks/{$task->id}/comments");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'body', 'parent_id', 'author' => ['id', 'name'], 'replies_count', 'replies', 'created_at'],
                ],
            ])
            ->assertJsonPath('data.0.replies_count', 5);

        // Preview is capped (3), not the full 5.
        $this->assertCount(3, $response->json('data.0.replies'));
    }

    public function test_replies_endpoint_paginates(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();
        $root = Comment::factory()->create(['task_id' => $task->id]);
        Comment::factory()->count(12)->reply($root)->create();

        $response = $this->getJson("/api/comments/{$root->id}/replies");

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_author_can_delete_own_comment_and_replies_cascade(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();
        $root = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $user->id]);
        $reply = Comment::factory()->reply($root)->create();

        $this->deleteJson("/api/comments/{$root->id}")->assertNoContent();

        $this->assertDatabaseMissing('comments', ['id' => $root->id]);
        $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
    }

    public function test_non_author_cannot_delete_comment(): void
    {
        $task = Task::factory()->create();
        $root = Comment::factory()->create(['task_id' => $task->id]);
        $this->actingUser(); // not the author

        $this->deleteJson("/api/comments/{$root->id}")->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $root->id]);
    }

    public function test_posting_root_comment_dispatches_comment_created_event(): void
    {
        Event::fake([CommentCreated::class, ReplyCreated::class]);
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();

        $this->postJson("/api/tasks/{$task->id}/comments", ['body' => 'root'])->assertCreated();

        Event::assertDispatched(CommentCreated::class);
        Event::assertNotDispatched(ReplyCreated::class);
    }

    public function test_posting_reply_dispatches_reply_created_event(): void
    {
        Event::fake([CommentCreated::class, ReplyCreated::class]);
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();
        $root = Comment::factory()->create(['task_id' => $task->id]);

        $this->postJson("/api/tasks/{$task->id}/comments", [
            'body' => 'reply',
            'parent_id' => $root->id,
        ])->assertCreated();

        Event::assertDispatched(ReplyCreated::class);
        Event::assertNotDispatched(CommentCreated::class);
    }
}
