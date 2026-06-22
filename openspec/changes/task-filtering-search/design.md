## Context

`TaskController@index` currently runs `$request->user()->tasks()->latest()->get()` and returns raw JSON. There is no filtering. The frontend `TaskList.vue` fetches once on mount and renders everything.

Two concerns drive this design:
1. Add server-side `search` (name) + `status` filtering that compose.
2. Make the query mechanism swappable to demonstrate the service container + repository (strategy) pattern, so a hand-written engine can be replaced by `spatie/laravel-query-builder` via config without changing the controller.

Constraints: Laravel 12, PHP 8.2+, Sanctum auth, thin controllers, Eloquent scopes, PHPUnit. No dependency upgrades; `spatie/laravel-query-builder` is a new dependency (needs approval).

## Goals / Non-Goals

**Goals:**
- `GET /api/tasks?search=&status=` filters server-side, composing both filters, scoped to the user, newest-first preserved.
- Reusable `Task` query scopes (`scopeSearch`, `scopeStatus`).
- `TaskRepositoryInterface` bound in the container; `classic` and `spatie` implementations selectable via `TASK_FILTER_ENGINE`.
- Tests proving search, status, combined filtering, user-scoping, validation, and engine parity.
- Frontend search box + status dropdown wired to the API.

**Non-Goals:**
- Pagination, sorting by arbitrary columns, priority/due-date filtering (future stages).
- API Resources / Form Requests refactor (separate improvement surface).
- Changing auth or ownership model.

## Decisions

### 1. Eloquent scopes hold the filter logic
`scopeSearch(Builder $q, ?string $term)` and `scopeStatus(Builder $q, ?string $status)` on `Task`. Each is a no-op when its argument is blank/`all`, so callers can chain unconditionally: `->search($term)->status($status)`. This keeps filter semantics in one place, reused by the classic repository and available to the spatie one.
- *Alternative:* inline `when()` clauses in the controller — rejected; duplicates logic and bypasses the repository abstraction.
- `search` uses `where('name', 'like', "%{$term}%")`. Case-insensitivity holds on the assessment's default (SQLite/MySQL `like` is case-insensitive for ASCII); acceptable for scope.

### 2. Repository contract + container binding (strategy pattern)
```
App\Repositories\TaskRepositoryInterface
  public function filterForUser(User $user, array $filters): Collection
App\Repositories\ClassicTaskRepository   // uses model scopes
App\Repositories\SpatieTaskRepository     // uses spatie/laravel-query-builder
```
`$filters` is a normalized array shape `{ search?: string, status?: string }`. Binding lives in `AppServiceProvider::register()`:
```php
$this->app->bind(TaskRepositoryInterface::class, function ($app) {
    return match (config('tasks.filter_engine')) {
        'spatie' => $app->make(SpatieTaskRepository::class),
        default  => $app->make(ClassicTaskRepository::class),
    };
});
```
Controller type-hints `TaskRepositoryInterface` and calls `filterForUser`. Returns a `Collection` (matches current non-paginated contract).
- *Alternative:* a single class with an `if` — rejected; the explicit interface + two classes is the point (demonstrates the pattern, keeps each engine isolated, makes parity testable).

### 3. Config + env toggle
`config/tasks.php` → `'filter_engine' => env('TASK_FILTER_ENGINE', 'classic')`. Default `classic` so no env change is needed and behavior is deterministic in CI. Add `TASK_FILTER_ENGINE=classic` to `.env.example`.

### 4. Input validation
Validate query params in the controller (or a `IndexTaskRequest` form request): `search` `nullable|string|max:255`, `status` `nullable|in:todo,in_progress,done,all`. Invalid `status` → `422`. The spatie engine still receives the same validated `$filters` array (we do not expose raw spatie `filter[...]` syntax to keep both engines on one API contract).

### 5. Single API Resource for all task responses
Add `app/Http/Resources/TaskResource.php` exposing `id, name, description, status, priority, due_date, created_at, updated_at` (no `user_id`). Every `TaskController` action returns through it: `show`/`store`/`update` → `new TaskResource($task)`; `index` → `TaskResource::collection($tasks)`. One serialization layer, consistent shape, decoupled from model internals.
- *Alternative:* return raw models — rejected; leaks `user_id`, no single place to evolve the shape.

**Envelope decision — must align frontend.** A `ResourceCollection` wraps output in a top-level `data` key by default, so `GET /api/tasks` becomes `{ "data": [...] }` and single-resource responses become `{ "data": {...} }`. The current frontend reads `response.data` as the array directly. Two options:
- **(a) Keep the `data` envelope** (idiomatic Laravel) and update the frontend to read `response.data.data` for the list and `response.data.data` for single fetches in `TaskShow`/`TaskEdit`.
- **(b) Disable wrapping** via `JsonResource::withoutWrapping()` to preserve the exact current payload shape and avoid frontend churn.

Decision: **(a) keep the envelope** — it is the documented default and the assessment rewards idiomatic resources; update the affected frontend reads accordingly. Audit `TaskList.vue`, `TaskShow.vue`, `TaskEdit.vue` for `.data` access during apply.

### 6. Engine parity via shared filter shape
Both repositories consume the identical normalized `$filters` array and apply the same logical filters, so a parity test (same data, toggle config, assert equal IDs) is meaningful. The spatie implementation uses `QueryBuilder::for($user->tasks())` with `AllowedFilter::partial('search', 'name')` (or a callback) and an exact `status` filter, then maps our params onto spatie's expectations internally.

## Risks / Trade-offs

- **New dependency needs approval** (`spatie/laravel-query-builder`) → call out in tasks; classic engine works standalone if approval is withheld (spatie work can be deferred without blocking the feature).
- **`like` case-sensitivity varies by DB collation** → acceptable for SQLite/MySQL defaults used here; note in code. If Postgres were used, switch to `ilike`.
- **Two engines drift** → mitigated by the parity test running the same scenarios under both configs.
- **Spatie expects its own query-param syntax** → mitigated by mapping our `search`/`status` params to spatie filters inside `SpatieTaskRepository`, keeping the public API identical across engines.
- **Returning a `Collection` (not paginator)** → keeps current frontend contract; pagination is a deliberate non-goal.

## Migration Plan

1. Add scopes + tests (classic path) — backward compatible (no params = current behavior).
2. Add interface, classic repo, config, container binding; switch controller to delegate.
3. Add frontend controls.
4. Add `spatie/laravel-query-builder` (after approval) + `SpatieTaskRepository` + parity test.

Rollback: set `TASK_FILTER_ENGINE=classic` (default) to disable the spatie path; revert is purely additive otherwise.

## Open Questions

- Use a dedicated `IndexTaskRequest` form request or inline validation? (Lean form request for cleanliness, but inline matches current controller style — decide at apply time.)
- Confirm approval to add `spatie/laravel-query-builder` to composer.
