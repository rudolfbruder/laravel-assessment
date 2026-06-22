<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Repositories\ClassicTaskRepository;
use App\Repositories\SpatieTaskRepository;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskFilterEngineParityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: array{search?: string, status?: string}}>
     */
    public static function filterScenarios(): array
    {
        return [
            'no filters' => [[]],
            'search only' => [['search' => 'report']],
            'status only' => [['status' => 'todo']],
            'status all' => [['status' => 'all']],
            'search and status' => [['search' => 'report', 'status' => 'todo']],
        ];
    }

    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    #[DataProvider('filterScenarios')]
    public function test_classic_and_spatie_engines_return_equivalent_results(array $filters): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['name' => 'Weekly report', 'status' => 'todo']);
        Task::factory()->for($user)->create(['name' => 'Weekly report', 'status' => 'done']);
        Task::factory()->for($user)->create(['name' => 'Other thing', 'status' => 'todo']);
        Task::factory()->for($user)->create(['name' => 'Annual REPORT', 'status' => 'in_progress']);

        $classic = app(ClassicTaskRepository::class)->filterForUser($user, $filters)->pluck('id')->all();
        $spatie = app(SpatieTaskRepository::class)->filterForUser($user, $filters)->pluck('id')->all();

        $this->assertSame($classic, $spatie);
    }

    public function test_container_resolves_engine_from_config(): void
    {
        config(['tasks.filter_engine' => 'classic']);
        $this->assertInstanceOf(ClassicTaskRepository::class, app(TaskRepositoryInterface::class));

        config(['tasks.filter_engine' => 'spatie']);
        $this->assertInstanceOf(SpatieTaskRepository::class, app(TaskRepositoryInterface::class));
    }
}
