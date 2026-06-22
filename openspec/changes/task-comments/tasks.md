## 1. Database & model

- [ ] 1.1 Create `comments` migration: `task_id` FK cascade, `user_id` FK cascade, `parent_id` nullable self-FK cascade, `body` text, timestamps, index `(task_id, parent_id)`
- [ ] 1.2 Create `Comment` model: fillable `task_id, user_id, parent_id, body`; relations `user()` (author), `task()`, `parent()`, `replies()` (hasMany self)
- [ ] 1.3 Add `comments()` / root-scope relation on `Task` (and `replies()` convenience); add `hasMany(Comment)` on `User` if useful
- [ ] 1.4 Create `CommentFactory` with a `reply()` state (sets `parent_id`)

## 2. Events

- [ ] 2.1 Create `App\Events\CommentCreated` (holds `Comment`, `Dispatchable`, `SerializesModels`)
- [ ] 2.2 Create `App\Events\ReplyCreated` (holds `Comment`)

## 3. Validation & resource

- [ ] 3.1 Create `StoreCommentRequest`: trim `body` in `prepareForValidation`; rules `body` required|string|min:1|max:2000 (whitespace-only rejected); `parent_id` nullable|integer|exists, must belong to the route task AND be a root comment (custom rule → 422)
- [ ] 3.2 Create `CommentResource`: `id, body, parent_id, author{id,name}, created_at, replies_count`, plus `replies` preview when loaded (never expose author email)

## 4. Controller, routes, dispatch

- [ ] 4.1 Create `CommentController@index` — root comments for task, paginated, with `withCount('replies')` + first-page replies preview, via `CommentResource`
- [ ] 4.2 `CommentController@store` — create comment/reply under task with author = current user; dispatch `ReplyCreated` if `parent_id` present else `CommentCreated`; return 201 resource
- [ ] 4.3 `CommentController@replies` — paginated replies for a root comment via `CommentResource`
- [ ] 4.4 `CommentController@destroy` — author-only (CommentPolicy `delete`), cascade replies, return 204
- [ ] 4.5 Register routes: shallow `apiResource('tasks.comments')` limited to index/store/destroy + `GET comments/{comment}/replies`, all under `auth:sanctum`
- [ ] 4.6 Create + register `CommentPolicy` (delete = author match)

## 5. Backend tests

- [ ] 5.1 Test: authenticated user posts comment → 201, persisted with author/task/timestamp
- [ ] 5.2 Test: any authenticated user (non-owner of task) can comment → 201
- [ ] 5.3 Test: unauthenticated → 401
- [ ] 5.4 Test: body validation — missing, whitespace-only, over-2000 → 422 on `body`
- [ ] 5.5 Test: valid reply to root → 201 stored as reply
- [ ] 5.6 Test: reply to a reply (non-root) → 422 on `parent_id`
- [ ] 5.7 Test: parent from another task → 422 on `parent_id`
- [ ] 5.8 Test: index returns roots with author, `replies_count`, replies preview; replies endpoint paginates
- [ ] 5.9 Test: author deletes own comment → 204 and replies cascade; non-author → 403
- [ ] 5.10 Test: `Event::fake()` — root dispatches `CommentCreated`, reply dispatches `ReplyCreated`

## 6. Frontend (TaskShow.vue)

- [ ] 6.1 Replace comments placeholder with a composer (textarea + submit) posting to `/tasks/{id}/comments`; show inline validation errors (422 `body`)
- [ ] 6.2 Load + render root comments (author name + relative timestamp + body) with their replies preview
- [ ] 6.3 Per-root inline reply composer posting with `parent_id`; show validation errors
- [ ] 6.4 "Show N more replies" control that lazily fetches `/comments/{id}/replies?page=` and appends
- [ ] 6.5 Author-only delete control on own comments (calls DELETE, removes from list)
- [ ] 6.6 Optional: extract a `CommentItem.vue` component if TaskShow grows unwieldy

## 7. Finalize

- [ ] 7.1 Run `vendor/bin/pint --dirty --format agent`
- [ ] 7.2 Run the comment tests; then ask user to run full suite
