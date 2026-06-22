## 1. Model scopes

- [ ] 1.1 Add `scopeSearch(Builder $query, ?string $term)` to `Task` — partial, case-insensitive `name` `like` match; no-op when term is blank
- [ ] 1.2 Add `scopeStatus(Builder $query, ?string $status)` to `Task` — exact match; no-op when status is blank or `all`
- [ ] 1.3 Add a unit/feature test asserting each scope filters correctly and no-ops on blank input

## 2. Repository contract (classic engine)

- [ ] 2.1 Create `App\Repositories\TaskRepositoryInterface` with `filterForUser(User $user, array $filters): Collection`
- [ ] 2.2 Create `App\Repositories\ClassicTaskRepository` using the model scopes + `latest()`, scoped to the user
- [ ] 2.3 Add `config/tasks.php` with `'filter_engine' => env('TASK_FILTER_ENGINE', 'classic')`
- [ ] 2.4 Bind `TaskRepositoryInterface` in `AppServiceProvider::register()` via `match(config('tasks.filter_engine'))`, defaulting to classic
- [ ] 2.5 Add `TASK_FILTER_ENGINE=classic` to `.env.example`

## 3. API Resource

- [ ] 3.1 Create `app/Http/Resources/TaskResource.php` exposing `id, name, description, status, priority, due_date, created_at, updated_at` (omit `user_id`)
- [ ] 3.2 Return resources from all `TaskController` actions: `new TaskResource($task)` for `show`/`store`/`update`, `TaskResource::collection(...)` for `index`
- [ ] 3.3 Decide envelope: keep default `data` wrapping (chosen) and update frontend reads accordingly

## 4. Controller wiring + validation

- [ ] 4.1 Validate `search` (`nullable|string|max:255`) and `status` (`nullable|in:todo,in_progress,done,all`) in `TaskController@index` (inline or `IndexTaskRequest`)
- [ ] 4.2 Normalize validated params into the `$filters` array shape and delegate to the injected `TaskRepositoryInterface`
- [ ] 4.3 Return the filtered collection through `TaskResource::collection` (preserve newest-first order)

## 5. Backend tests

- [ ] 5.1 Test: `search` returns only name-matching tasks (case-insensitive, partial)
- [ ] 5.2 Test: `status` returns only matching status; `all`/absent returns every status
- [ ] 5.3 Test: combined `search`+`status` applies both (AND)
- [ ] 5.4 Test: filtering never crosses users
- [ ] 5.5 Test: invalid `status` returns `422`
- [ ] 5.6 Test: no params behaves like the original index (backward compatible)
- [ ] 5.7 Test: responses use `TaskResource` shape (keys present, `user_id` absent) for index + show

## 6. Frontend (TaskList.vue)

- [ ] 6.1 Add a search text input and a status `<select>` (All / To Do / In Progress / Done)
- [ ] 6.2 Pass `search` + `status` as query params to `api.get('/tasks', { params })` and refetch on change (debounce search)
- [ ] 6.3 Update `.data` reads for the `data` envelope across `TaskList.vue`, `TaskShow.vue`, `TaskEdit.vue`
- [ ] 6.4 Handle empty-result state when filters match nothing (distinct from "no tasks yet")

## 7. Spatie engine (after dependency approval)

- [ ] 7.1 Get approval and add `spatie/laravel-query-builder` to composer
- [ ] 7.2 Create `App\Repositories\SpatieTaskRepository` implementing the interface; map `search`/`status` params to spatie filters on `$user->tasks()`
- [ ] 7.3 Parity test: same dataset + scenarios under `TASK_FILTER_ENGINE=classic` and `=spatie` return equivalent task IDs

## 8. Finalize

- [ ] 8.1 Run `vendor/bin/pint --dirty --format agent`
- [ ] 8.2 Run the task-filtering tests; then ask user to run full suite
