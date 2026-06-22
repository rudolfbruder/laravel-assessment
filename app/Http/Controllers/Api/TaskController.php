<?php

namespace App\Http\Controllers\Api;

use App\Domain\Tasks\Aggregates\TaskAggregateRoot;
use App\Domain\Tasks\StoredEvents\TaskStoredEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskEventResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function __construct(private TaskRepositoryInterface $tasks) {}

    /**
     * Display a filtered listing of the authenticated user's tasks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:todo,in_progress,done,all',
        ]);

        return TaskResource::collection(
            $this->tasks->filterForUser($request->user(), $filters)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,done',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $uuid = (string) Str::uuid();

        TaskAggregateRoot::retrieve($uuid)
            ->createTask($request->user()->id, $validated)
            ->persist();

        $task = Task::query()->where('uuid', $uuid)->firstOrFail();

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * Any authenticated user may view a task (e.g. to read/post comments);
     * only the owner may update or delete it.
     */
    public function show(Request $request, Task $task): TaskResource
    {
        return TaskResource::make($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task): TaskResource
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:todo,in_progress,done',
            'priority' => 'sometimes|required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        TaskAggregateRoot::retrieve($task->uuid)
            ->updateTask($validated)
            ->persist();

        return TaskResource::make($task->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task): Response
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403);
        }

        TaskAggregateRoot::retrieve($task->uuid)
            ->deleteTask()
            ->persist();

        return response()->noContent();
    }

    /**
     * Display the event-sourced activity timeline for the given task.
     */
    public function events(Request $request, Task $task): AnonymousResourceCollection
    {
        return TaskEventResource::collection(
            TaskStoredEvent::query()
                ->where('aggregate_uuid', $task->uuid)
                ->orderBy('id')
                ->get()
        );
    }
}
