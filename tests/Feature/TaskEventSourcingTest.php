<?php

namespace Tests\Feature;

use App\Domain\Tasks\Events\TaskCreated;
use App\Domain\Tasks\Events\TaskDeleted;
use App\Domain\Tasks\Events\TaskUpdated;
use App\Domain\Tasks\StoredEvents\TaskStoredEvent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskEventSourcingTest extends TestCase
{
    use RefreshDatabase;

    public function test_storing_a_task_records_a_created_event_and_projects_the_model(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'name' => 'Write event sourcing tests',
            'description' => 'Cover create/update/delete',
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => '2026-07-01',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Write event sourcing tests');

        $task = Task::firstOrFail();
        $this->assertNotNull($task->uuid);
        $this->assertSame($user->id, $task->user_id);

        $stored = TaskStoredEvent::where('aggregate_uuid', $task->uuid)->get();
        $this->assertCount(1, $stored);
        $this->assertSame(TaskCreated::class, $stored->first()->event_class);
    }

    public function test_updating_a_task_records_an_updated_event_and_projects_changes(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $task = Task::factory()->for($user)->create(['status' => 'todo']);

        $this->putJson("/api/tasks/{$task->id}", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');

        $this->assertSame('done', $task->fresh()->status);

        $stored = TaskStoredEvent::where('aggregate_uuid', $task->uuid)
            ->where('event_class', TaskUpdated::class)
            ->first();
        $this->assertNotNull($stored);
        $this->assertSame('done', $stored->event_properties['changes']['status']);
    }

    public function test_deleting_a_task_records_a_deleted_event_and_removes_the_model(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $task = Task::factory()->for($user)->create();
        $uuid = $task->uuid;

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('task_stored_events', [
            'aggregate_uuid' => $uuid,
            'event_class' => TaskDeleted::class,
        ]);
    }

    public function test_timeline_endpoint_returns_events_in_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/tasks', [
            'name' => 'Timeline task',
            'status' => 'todo',
            'priority' => 'low',
        ])->assertCreated();

        $task = Task::firstOrFail();
        $this->putJson("/api/tasks/{$task->id}", ['priority' => 'high'])->assertOk();

        $this->getJson("/api/tasks/{$task->id}/events")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.label', 'Created')
            ->assertJsonPath('data.1.label', 'Updated')
            ->assertJsonPath('data.1.changes.priority', 'high');
    }

    public function test_non_owner_cannot_update_or_delete_a_task(): void
    {
        $owner = User::factory()->create();
        $task = Task::factory()->for($owner)->create();

        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/tasks/{$task->id}", ['name' => 'Hijack'])->assertForbidden();
        $this->deleteJson("/api/tasks/{$task->id}")->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }
}
