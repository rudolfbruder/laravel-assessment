## Context

Comments broadcast in real time on a per-task private channel; `TaskShow` live-merges them. Now we need cross-page notifications: any user gets notified when a task gets a new top-level comment. Existing events: `CommentCreated` (root) / `ReplyCreated` (reply) — only `CommentCreated` is relevant. `QUEUE_CONNECTION=database` and no guaranteed worker; `App.Models.User.{id}` private channel already authorized in `channels.php`. Domain-driven layout under `app/Domain/Comments/`.

## Goals / Non-Goals

**Goals:**
- Notify all authenticated users except the author when a root comment is posted.
- Live toast without refresh (no queue worker required) + persistent bell with unread count and mark-read.
- Informative payload: actor name, task id/name, comment id + excerpt.

**Non-Goals:**
- Reply notifications, email/other channels, preferences/muting, task-change notifications.

## Decisions

### 1. Split live vs persisted (worker-agnostic + fan-out efficiency)
`QUEUE_CONNECTION=database`. The design intentionally needs **no queue worker**: the live path is a `ShouldBroadcastNow` event (broadcasts inline, independent of the queue connection) and the database notification write is synchronous (the notification is not `ShouldQueue`). A worker may run, but isn't required. The split is also kept because recipients are **all users**:
- **Live**: a dedicated `ShouldBroadcastNow` event `CommentNotificationBroadcast` on **one shared channel** — a single broadcast for everyone.
- **Persisted**: a Laravel notification `NewCommentNotification` via the **`database`** channel only.
- *Alternative:* one `Notification` on `['broadcast','database']` broadcasting per-user — idiomatic, but (a) its broadcast channel enqueues `BroadcastNotificationCreated` (would need a worker on the database queue), and (b) with the all-users audience it fans out one broadcast **per recipient**. Rejected; the shared-channel `ShouldBroadcastNow` event is worker-free and far cheaper. Revisit if recipients narrow to owner+participants.

### 2. One shared channel for the toast
`CommentNotificationBroadcast` broadcasts on a single `PrivateChannel('comments.notifications')` (authorized for any authenticated user). One broadcast reaches all viewers — efficient for the "all users" choice instead of one broadcast per recipient. Payload: `{ actor_id, actor_name, task_id, task_name, comment_id, excerpt, created_at }`. Each client ignores events where `actor_id` is the current user. `broadcastAs`: `comment.notification`.

### 3. Listener does both, registered explicitly
`NotifyUsersOfComment` listens to `CommentCreated` (root-only by construction — replies fire `ReplyCreated`). It (a) sends `NewCommentNotification` to `User::where('id','!=',actorId)` in chunks, and (b) `broadcast(new CommentNotificationBroadcast(...))`. Registered via `Event::listen` in `AppServiceProvider::boot()` (domain path isn't auto-discovered). Listener runs synchronously (not `ShouldQueue`) so both paths fire in-request.
- **Scale caveat:** notifying *all* users writes N rows per comment. Acceptable for the demo; chunked (e.g. 200) and `log()`-free. Flagged for future targeting (owner + participants).

### 4. Notification payload & persistence
`NewCommentNotification::toArray` (= database payload) mirrors the broadcast payload. Stored in the standard `notifications` table (`make:notifications-table` migration). The bell reads `$user->notifications()` / `unreadNotifications()`.

### 5. Notification API (generic, app-level)
`NotificationController` (auth:sanctum), operating on `$request->user()`:
- `GET /api/notifications` — paginated list + `unread_count` (e.g. in a meta/extra field or a second field).
- `POST /api/notifications/{id}/read` — mark one read (404/forbidden if not the user's).
- `POST /api/notifications/read-all` — mark all read.
Generic notification reads live app-level (not comment-domain) since they operate on `DatabaseNotification`.

### 6. Frontend
`useNotifications.js` composable (singleton-ish, like `useAuth`): reactive `items`, `unreadCount`; `fetch()`, `markRead(id)`, `markAllRead()`; `subscribe()` via `useEcho('comments.notifications', '.comment.notification', handler, [], 'private')`. On a live event from another user: show a toast and `fetch()` (the DB row already exists, written synchronously during the comment request) to refresh list + count. App-level bell in `App.vue` navbar (unread badge, dropdown list; clicking an item marks read and routes to the task). A lightweight toast region in `App.vue`.

## Risks / Trade-offs

- **All-users fan-out** → N DB writes per comment; chunked, demo-scale only. Future: target owner + participants.
- **Two payload definitions** (broadcast event + notification) → minor duplication; kept in sync by a shared shape.
- **Live event carries no DB id** → frontend re-fetches to obtain mark-readable rows; small extra request, always correct since the DB write precedes the broadcast.
- **Reverb + no worker** → live path uses `ShouldBroadcastNow`; DB path is synchronous; neither needs `queue:work`.
- **Channel is shared** → any authed user sees all comment-notification traffic; payload contains no sensitive data (names/titles only), and viewing tasks is already open. Acceptable.

## Migration Plan

1. `notifications` table migration; `NewCommentNotification` (database); `CommentNotificationBroadcast` (ShouldBroadcastNow); `NotifyUsersOfComment` listener + registration; `comments.notifications` channel auth.
2. `NotificationController` + routes.
3. Frontend composable + bell + toast; subscribe globally.
4. Tests; `npm run build`.

Rollback: remove the listener registration (comments still broadcast/persist nothing extra) and the bell; additive otherwise.

## Open Questions

- None blocking. Recipient targeting is intentionally "all users" per the current decision; revisit for scale.
