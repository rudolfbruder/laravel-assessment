<?php

use App\Domain\Comments\Http\Controllers\CommentController;
use App\Events\TaskViewed;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TaskController;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('tasks/{task}/events', [TaskController::class, 'events']);
    Route::apiResource('tasks', TaskController::class);

    Route::apiResource('tasks.comments', CommentController::class)
        ->shallow()
        ->only(['index', 'store', 'destroy']);
    Route::get('comments/{comment}/replies', [CommentController::class, 'replies']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Sanity/presence: announce that the current user opened a task.
    Route::post('tasks/{task}/viewed', function (Request $request, Task $task) {
        broadcast(new TaskViewed($task, $request->user()));

        return response()->noContent();
    });
});
