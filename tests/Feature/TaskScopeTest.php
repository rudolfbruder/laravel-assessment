<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_scope_matches_partial_name_case_insensitively(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['name' => 'Quarterly report']);
        Task::factory()->for($user)->create(['name' => 'Buy milk']);

        $results = $user->tasks()->search('REPORT')->get();

        $this->assertCount(1, $results);
        $this->assertSame('Quarterly report', $results->first()->name);
    }

    public function test_search_scope_is_noop_when_blank(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->count(3)->create();

        $this->assertCount(3, $user->tasks()->search(null)->get());
        $this->assertCount(3, $user->tasks()->search('   ')->get());
    }

    public function test_status_scope_filters_by_exact_status(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['status' => 'done']);
        Task::factory()->for($user)->create(['status' => 'todo']);

        $results = $user->tasks()->status('done')->get();

        $this->assertCount(1, $results);
        $this->assertSame('done', $results->first()->status);
    }

    public function test_status_scope_is_noop_when_blank_or_all(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->count(3)->create(['status' => 'todo']);

        $this->assertCount(3, $user->tasks()->status(null)->get());
        $this->assertCount(3, $user->tasks()->status('all')->get());
    }
}
