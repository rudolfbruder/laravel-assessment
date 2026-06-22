<?php

namespace App\Providers;

use App\Domain\Comments\Events\CommentCreated;
use App\Domain\Comments\Listeners\NotifyUsersOfComment;
use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Policies\CommentPolicy;
use App\Repositories\ClassicTaskRepository;
use App\Repositories\SpatieTaskRepository;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TaskRepositoryInterface::class, function ($app) {
            return match (config('tasks.filter_engine')) {
                'spatie' => $app->make(SpatieTaskRepository::class),
                default => $app->make(ClassicTaskRepository::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Policy lives in the Comments domain, outside the auto-discovered App\Policies path.
        Gate::policy(Comment::class, CommentPolicy::class);

        // Domain listener isn't auto-discovered (lives outside App\Listeners).
        Event::listen(CommentCreated::class, NotifyUsersOfComment::class);
    }
}
