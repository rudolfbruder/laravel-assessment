<?php

namespace App\Providers;

use App\Repositories\ClassicTaskRepository;
use App\Repositories\SpatieTaskRepository;
use App\Repositories\TaskRepositoryInterface;
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
        //
    }
}
