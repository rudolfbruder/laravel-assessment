## Why

Tasks have no way to capture discussion. The task detail page already shows a "Comments feature is not implemented yet" placeholder. Users need to comment on a task, reply to a comment, and see who said what and when — a full-stack feature (DB, API, UI). The data and events are also the foundation for a later real-time broadcasting stage (Laravel Reverb).

## What Changes

- New `comments` table: self-referential (`parent_id` nullable FK to `comments`) so a reply is just a comment pointing at a root comment. **One level of threading** — replies attach to root comments only; reply-to-reply is rejected.
- New `Comment` model: `belongsTo(User)` (author), `belongsTo(Task)`, `belongsTo(parent)`, `hasMany(replies)`.
- REST endpoints under `auth:sanctum`:
  - `GET /api/tasks/{task}/comments` — root comments (paginated), each with author, timestamp, `replies_count`, and a first page of replies.
  - `POST /api/tasks/{task}/comments` — create a comment or reply (`parent_id` optional).
  - `GET /api/comments/{comment}/replies` — paginated replies for a root comment (lazy "show N more replies").
  - `DELETE /api/comments/{comment}` — author-only delete; cascades replies.
- `CommentResource` (+ author sub-shape) with nested first-page replies and counts.
- Validation: `body` required, trimmed, `min:1`, `max:2000`, not whitespace-only; `parent_id` nullable, must exist, belong to the **same task**, and be a **root** comment (depth cap → 422 otherwise).
- **Authorization (per decision): any authenticated user may view/post comments on any task.** Comment endpoints do not enforce task ownership. `DELETE` is author-only. (Documented divergence from the current owner-only task isolation — see design.)
- Events: dispatch `CommentCreated` when a root comment is posted and `ReplyCreated` when a reply is posted. Plain events now; `ShouldBroadcast` + Reverb wiring is the later stage, out of scope here.
- Frontend `TaskShow.vue`: replace the placeholder with a comment composer, a list of root comments (author + relative timestamp), inline reply composer, and a "+N more replies" control that lazily fetches additional replies. Inline validation-error display.

## Capabilities

### New Capabilities
- `task-comments`: Authenticated users can comment on tasks and reply (one level) to comments, with author/timestamp display, validated input, author-only delete, lazy reply loading, and domain events for a future broadcasting stage.

### Modified Capabilities
<!-- None. task-resource and task-filtering specs are unaffected. -->

## Impact

- **DB**: new `comments` migration; cascade on `task_id` and `parent_id`.
- **Backend**: `app/Models/Comment.php`, `CommentController`, `StoreCommentRequest`, `app/Http/Resources/CommentResource.php`, `app/Events/CommentCreated.php`, `app/Events/ReplyCreated.php`, route additions in `routes/api.php`, `CommentFactory`.
- **Frontend**: `resources/js/pages/TaskShow.vue` (comments UI); possibly a small `CommentItem` component.
- **Tests**: feature tests for create/reply/validation/depth-cap/delete/auth + event dispatch assertions; factory.
- **Out of scope**: broadcasting/Reverb, comment editing, polymorphic comments on non-task models.
