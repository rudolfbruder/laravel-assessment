## Context

`TaskShow.vue` renders a hard-coded "Comments feature is not implemented yet" placeholder. There is no comments storage, API, or events. Stack: Laravel 12, Sanctum, Vue 3 SPA, PHPUnit, SQLite in tests. Existing conventions established in the prior change: API Resources for serialization, thin controllers, repository binding for tasks (comments do not need a repository — no engine toggle), feature tests with Sanctum::actingAs.

Requirements come from Task 2 plus user decisions: self-referential single table; one-level threading with lazily-loaded replies; author-only delete; separate `CommentCreated`/`ReplyCreated` events; comments open to any authenticated user.

## Goals / Non-Goals

**Goals:**
- One `comments` table, self-referential, one level of replies enforced.
- Authenticated users post comments/replies on any task; author + timestamp surfaced.
- Body validation (required, trimmed, 1–2000, not whitespace-only); `parent_id` integrity (same task, root only).
- Author-only delete with reply cascade.
- Root comment list paginated; replies lazily paginated.
- `CommentCreated` / `ReplyCreated` dispatched on create (broadcast-ready later).
- Full-stack: migration, model, controller, request, resource, events, factory, tests, TaskShow UI.

**Non-Goals:**
- Broadcasting / Reverb / WebSockets (next stage; events are the seam).
- Comment editing.
- Unlimited nesting; polymorphic comments on other models.
- Changing task visibility rules.

## Decisions

### 1. Schema — single self-referential table, depth capped at 1
```
comments
  id
  task_id      FK -> tasks, onDelete cascade
  user_id      FK -> users, onDelete cascade   (author)
  parent_id    nullable FK -> comments, onDelete cascade
  body         text
  timestamps
  index (task_id, parent_id)   // list roots / replies efficiently
```
Adjacency list is correct and avoids a redundant `replies` table. Depth is capped to one level in **validation** (a reply's `parent_id` must be a root), not in the schema — the column stays general so a future "unlimited nesting" decision is a validation change, not a migration. `onDelete('cascade')` on `parent_id` gives reply cleanup for free.
- *Alternative:* nested-set / closure table — rejected; massive overkill for one-level threads.

### 2. One level, but replies loaded lazily
Per the user's "edit-style" loading: the task page must not pull entire threads on load. So:
- `GET /api/tasks/{task}/comments` returns **root comments paginated** (e.g. 15/page). Each root carries `replies_count` and `replies_preview` (first N, e.g. 3, oldest-first).
- `GET /api/comments/{comment}/replies?page=` returns the rest, paginated (e.g. 10/page), oldest-first.
- Frontend shows roots + preview replies + a "Show N more replies" button that fetches subsequent pages on click.
This keeps initial payload bounded and matches the requested UX.

### 3. Authorization — any authenticated user (with rationale)
Comment index/store endpoints require only `auth:sanctum`; they do **not** check task ownership. Rationale (user): future project-manager roles will view tasks they don't own, so comments must be open to any authenticated participant now. This intentionally diverges from `TaskController`'s owner-only `abort(403)`.
- **Documented risk:** today `TaskController@show` still 403s non-owners, so a non-owner can't reach the task in the UI to comment, even though the comment API would allow it. Acceptable: the API models the future state; UI is owner-only until task sharing lands. No change to task visibility in this change.
- `DELETE /api/comments/{comment}` is author-only via a `CommentPolicy` (`delete` = `user_id` match) or inline check — use a policy to match Laravel idiom and keep the controller thin.

### 4. Validation in a Form Request
`StoreCommentRequest`:
- `body`: `['required','string', tap-trim, 'min:1','max:2000']` with a rule rejecting whitespace-only (e.g. `prepareForValidation` trims, then `min:1` catches empty). Trim in `prepareForValidation` so stored body is clean.
- `parent_id`: `['nullable','integer', Rule::exists('comments','id')]` plus a custom check (closure rule) asserting the parent belongs to the route `{task}` **and** is a root (`parent_id` null). Both failures → `422` on `parent_id`.
Using a Form Request (vs inline) is the cleaner choice and was flagged as an improvement surface; comments is a good place to introduce it.

### 5. Resources
`CommentResource`: `id, body, author { id, name }, parent_id, created_at, replies_count, replies (preview, only when loaded)`. Author via `whenLoaded('user')` mapped to a minimal shape (never expose email). Replies preview via `whenLoaded('replies')` → `CommentResource::collection`. `replies_count` via `withCount('replies')`. Reuse `CommentResource` for the replies endpoint (without nested replies).

### 6. Events
`CommentCreated` and `ReplyCreated`, each a plain event holding the `Comment` (constructor-promoted, `Dispatchable`/`SerializesModels`). Controller (or model `created` observer) dispatches based on `parent_id`. Keeping them separate per user decision; both will implement `ShouldBroadcast` and define `broadcastOn`/`broadcastWith` in the later stage. Dispatch only after successful save.
- Dispatch from the **controller** after create (explicit, testable with `Event::fake()`), not a model observer — avoids firing during factory/seeder bulk creation in tests.

### 7. Routes
```
Route::apiResource('tasks.comments', CommentController::class)->shallow()->only(['index','store','destroy']);
Route::get('comments/{comment}/replies', [CommentController::class, 'replies']);
```
`shallow()` gives `tasks/{task}/comments` for index/store and `comments/{comment}` for destroy — clean and idiomatic.

## Risks / Trade-offs

- **Auth divergence (open comments vs owner-only tasks)** → documented; UI remains owner-reachable only. Revisit when task sharing/PM roles land. No security regression (still requires authentication; delete still author-scoped).
- **Reply pagination + preview count drift under concurrency** → acceptable; counts are advisory, fetch is authoritative.
- **Events fired from controller** → if comments are ever created outside the controller, events won't fire. Acceptable now (single creation path); can move to an observer when broadcasting lands.
- **Whitespace/trim handling** → centralized in `prepareForValidation` so both validation and storage see trimmed input; avoids "looks empty but passes" bugs.
- **Cascade deletes** → deleting a task removes its comments; deleting a root removes replies. Intended; no soft-deletes in scope.

## Migration Plan

1. Migration + `Comment` model + factory.
2. Request + resource + controller + routes + events; dispatch on create.
3. Feature tests (create, reply, depth cap, same-task parent, validation, delete auth, event dispatch, lazy replies).
4. TaskShow UI: composer, list, reply composer, lazy "show more replies", error display.

Rollback: additive; drop migration + new files. No changes to existing task endpoints.

## Open Questions

- Root comment ordering: newest-first vs oldest-first? Default oldest-first (chronological thread) for the page; revisit if the list grows. (Decide at apply time; not blocking.)
- Page sizes (roots 15, replies preview 3, replies page 10) — reasonable defaults, tune later.
