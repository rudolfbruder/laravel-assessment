<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_search_returns_only_name_matching_tasks(): void
    {
        $user = $this->actingUser();
        Task::factory()->for($user)->create(['name' => 'Quarterly report']);
        Task::factory()->for($user)->create(['name' => 'Buy milk']);

        $response = $this->getJson('/api/tasks?search=REPORT');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Quarterly report', $response->json('data.0.name'));
    }

    public function test_status_filters_to_single_status(): void
    {
        $user = $this->actingUser();
        Task::factory()->for($user)->create(['status' => 'done']);
        Task::factory()->for($user)->create(['status' => 'todo']);

        $response = $this->getJson('/api/tasks?status=done');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('done', $response->json('data.0.status'));
    }

    public function test_status_all_returns_every_status(): void
    {
        $user = $this->actingUser();
        Task::factory()->for($user)->create(['status' => 'done']);
        Task::factory()->for($user)->create(['status' => 'todo']);
        Task::factory()->for($user)->create(['status' => 'in_progress']);

        $this->getJson('/api/tasks?status=all')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_search_and_status_compose(): void
    {
        $user = $this->actingUser();
        $match = Task::factory()->for($user)->create(['name' => 'Weekly report', 'status' => 'todo']);
        Task::factory()->for($user)->create(['name' => 'Weekly report', 'status' => 'done']);
        Task::factory()->for($user)->create(['name' => 'Other thing', 'status' => 'todo']);

        $response = $this->getJson('/api/tasks?search=report&status=todo');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($match->id, $response->json('data.0.id'));
    }

    public function test_filtering_never_crosses_users(): void
    {
        $user = $this->actingUser();
        Task::factory()->for($user)->create(['name' => 'My report']);
        Task::factory()->create(['name' => 'Their report']);

        $response = $this->getJson('/api/tasks?search=report');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('My report', $response->json('data.0.name'));
    }

    public function test_invalid_status_returns_422(): void
    {
        $this->actingUser();

        $this->getJson('/api/tasks?status=archived')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_no_params_returns_all_user_tasks_newest_first(): void
    {
        $user = $this->actingUser();
        $older = Task::factory()->for($user)->create(['created_at' => now()->subDay()]);
        $newer = Task::factory()->for($user)->create(['created_at' => now()]);

        $response = $this->getJson('/api/tasks');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame($newer->id, $response->json('data.0.id'));
        $this->assertSame($older->id, $response->json('data.1.id'));
    }

    public function test_index_uses_task_resource_shape(): void
    {
        $user = $this->actingUser();
        Task::factory()->for($user)->create();

        $response = $this->getJson('/api/tasks');

        $response->assertOk()->assertJsonStructure([
            'data' => [
                ['id', 'name', 'description', 'status', 'priority', 'due_date', 'created_at', 'updated_at'],
            ],
        ]);
        $this->assertArrayNotHasKey('user_id', $response->json('data.0'));
    }

    public function test_show_uses_task_resource_shape(): void
    {
        $user = $this->actingUser();
        $task = Task::factory()->for($user)->create();

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertOk()->assertJsonStructure([
            'data' => ['id', 'name', 'description', 'status', 'priority', 'due_date', 'created_at', 'updated_at'],
        ]);
        $this->assertArrayNotHasKey('user_id', $response->json('data'));
    }
}
