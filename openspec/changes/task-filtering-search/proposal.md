## Why

The task list page (`GET /api/tasks`) returns every task for the user with no way to narrow results. Users need to find tasks by name and focus on a single status. Doing this in the browser does not scale and is not how a real API behaves — filtering must happen server-side.

Beyond the feature itself, this change is an opportunity to demonstrate clean architecture: a swappable query engine selected via configuration (a strategy/repository pattern resolved through the service container), so the simple hand-written implementation can later be replaced by `spatie/laravel-query-builder` without touching the controller.

## What Changes

- `GET /api/tasks` accepts two optional query parameters:
  - `search` — case-insensitive partial match on task `name`.
  - `status` — exact match on `todo | in_progress | done` (omitted/`all` = no status filter).
- Both filters compose (AND), and combine with the existing per-user scoping and `latest()` ordering.
- Add reusable query scopes on the `Task` model (`scopeSearch`, `scopeStatus`) for the filter behavior.
- Introduce a `TaskRepositoryInterface` bound in the service container, with two interchangeable implementations:
  - `ClassicTaskRepository` — hand-written `where` queries using the model scopes.
  - `SpatieTaskRepository` — same contract built on `spatie/laravel-query-builder` (added later in the same change).
- A `tasks.filter_engine` config value (driven by an env var, e.g. `TASK_FILTER_ENGINE=classic|spatie`) selects which implementation the container resolves.
- `TaskController@index` delegates filtering to the resolved repository instead of querying the model directly.
- Add a `TaskResource` (Eloquent API Resource). **All** task endpoints (`index`, `store`, `show`, `update`) return the model through `TaskResource` instead of raw JSON, giving a single explicit serialization layer. `index` uses `TaskResource::collection(...)`.
- Frontend `TaskList.vue` gains a search input and status dropdown (All / To Do / In Progress / Done) that pass the parameters to the API and refetch.

## Capabilities

### New Capabilities
- `task-filtering`: Server-side filtering and search of a user's tasks by name and status, with a configuration-selectable query engine behind a repository contract.
- `task-resource`: Consistent JSON serialization of tasks via a single Eloquent API Resource across all task endpoints.

### Modified Capabilities
<!-- No existing specs in openspec/specs/; nothing to modify. -->

## Impact

- **Backend**: `app/Models/Task.php` (scopes), `app/Http/Controllers/Api/TaskController.php` (delegate to repository, return resources), new `app/Http/Resources/TaskResource.php`, new `app/Repositories/` (interface + 2 implementations), new `app/Providers/` binding (or `AppServiceProvider`), new `config/tasks.php`, `.env`/`.env.example`.
- **Dependencies**: adds `spatie/laravel-query-builder` (composer) — requires approval per project conventions.
- **Frontend**: `resources/js/pages/TaskList.vue` (search + status controls, request params).
- **Tests**: new feature tests for index filtering (search, status, combined, engine toggle).
- **API**: backward compatible — no params behaves as before.
