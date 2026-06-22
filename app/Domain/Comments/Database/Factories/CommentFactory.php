<?php

namespace App\Domain\Comments\Database\Factories;

use App\Domain\Comments\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => $this->faker->paragraph(),
        ];
    }

    /**
     * Mark the comment as a reply to the given root comment.
     */
    public function reply(Comment $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'task_id' => $parent->task_id,
            'parent_id' => $parent->id,
        ]);
    }
}
