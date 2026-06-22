<!-- All comment code lives under app/Domain/Comments/ (domain-driven architecture). -->

## 1. Database, model, seeder

- [x] 1.1 Create `comments` migration: `task_id` FK cascade, `user_id` FK cascade, `parent_id` nullable self-FK cascade, `body` text, timestamps, index `(task_id, parent_id)`
- [x] 1.2 Create `App\Domain\Comments\Models\Comment`: fillable `task_id, user_id, parent_id, body`; relations `user()`, `task()`, `parent()`, `replies()`; `isRoot()`; `newFactory()` override
- [x] 1.3 Add `comments()` + `rootComments()` on `Task`; `comments()` on `User`
- [x] 1.4 Create `App\Domain\Comments\Database\Factories\CommentFactory` with a `reply()` state
- [x] 1.5 Create `App\Domain\Comments\Database\Seeders\CommentSeeder` (roots + replies per existing task); call it from `DatabaseSeeder`

## 2. Events

- [x] 2.1 Create `App\Domain\Comments\Events\CommentCreated` (holds `Comment`)
- [x] 2.2 Create `App\Domain\Comments\Events\ReplyCreated` (holds `Comment`)

## 3. Validation & resource

- [x] 3.1 Create `StoreCommentRequest`: trim `body` in `prepareForValidation`; `body` required|string|min:1|max:2000; `parent_id` nullable|integer|exists + same-task + root-only (422)
- [x] 3.2 Create `CommentResource`: `id, body, parent_id, author{id,name}, replies_count, replies (when loaded), created_at` (no author email)

## 4. Controller, routes, policy, dispatch

- [x] 4.1 `CommentController@index` — root comments paginated, `withCount('replies')` + limited reply preview, via `CommentResource`
- [x] 4.2 `CommentController@store` — create comment/reply with author = current user; dispatch `ReplyCreated`/`CommentCreated`; 201 resource
- [x] 4.3 `CommentController@replies` — paginated replies for a root comment
- [x] 4.4 `CommentController@destroy` — author-only (CommentPolicy), cascade replies, 204
- [x] 4.5 Register shallow `apiResource('tasks.comments')` (index/store/destroy) + `GET comments/{comment}/replies` under `auth:sanctum`
- [x] 4.6 Create `CommentPolicy` (delete = author); register via `Gate::policy` in `AppServiceProvider` (domain path not auto-discovered)

## 5. Backend tests

- [x] 5.1 Test: authenticated user posts comment → 201, persisted with author/task/timestamp
- [x] 5.2 Test: any authenticated non-owner can comment → 201
- [x] 5.3 Test: unauthenticated → 401
- [x] 5.4 Test: body validation — missing, whitespace-only, over-2000 → 422 on `body`
- [x] 5.5 Test: valid reply to root → 201 stored as reply
- [x] 5.6 Test: reply to a reply (non-root) → 422 on `parent_id`
- [x] 5.7 Test: parent from another task → 422 on `parent_id`
- [x] 5.8 Test: index returns roots with author, `replies_count`, capped reply preview; replies endpoint paginates
- [x] 5.9 Test: author deletes own comment → 204 and replies cascade; non-author → 403
- [x] 5.10 Test: `Event::fake()` — root dispatches `CommentCreated`, reply dispatches `ReplyCreated`

## 6. Frontend (TaskShow.vue)

- [x] 6.1 Comment composer posting to `/tasks/{id}/comments`; inline 422 `body` error display
- [x] 6.2 Separate axios call on page load fetches comments + reply preview; render author + timestamp + body
- [x] 6.3 Per-root inline reply composer posting with `parent_id`; validation errors
- [x] 6.4 "Show all/more replies" lazily fetching `/comments/{id}/replies?page=` and appending
- [x] 6.5 Author-only delete on own comment/reply (removes from list, adjusts count)

## 7. Finalize

- [x] 7.1 Run `vendor/bin/pint --dirty --format agent`
- [x] 7.2 Run the comment tests + full suite (36 passed)
- [x] 7.3 Update design.md to reflect domain-driven architecture + seeder
