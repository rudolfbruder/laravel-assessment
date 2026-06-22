<?php

namespace App\Domain\Comments\Database\Seeders;

use App\Domain\Comments\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CommentSeeder extends Seeder
{
    /**
     * Seed root comments and one level of replies for every existing task.
     */
    public function run(): void
    {
        /** @var Collection<int, User> $users */
        $users = User::all();

        if ($users->isEmpty()) {
            $users = User::factory()->count(3)->create();
        }

        Task::query()->each(function (Task $task) use ($users): void {
            $roots = Comment::factory()
                ->count(random_int(2, 4))
                ->create([
                    'task_id' => $task->id,
                    'user_id' => fn (): int => $users->random()->id,
                ]);

            $roots->each(function (Comment $root) use ($users): void {
                Comment::factory()
                    ->count(random_int(0, 3))
                    ->reply($root)
                    ->create([
                        'user_id' => fn (): int => $users->random()->id,
                    ]);
            });
        });
    }
}
