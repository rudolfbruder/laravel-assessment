<!-- Builds on comment-broadcasting. Live path uses ShouldBroadcastNow (no queue worker). -->

## 1. Persistence & notification

- [x] 1.1 Create the `notifications` table migration (`php artisan make:notifications-table`)
- [x] 1.2 Create `App\Domain\Comments\Notifications\NewCommentNotification` via the `database` channel; `toArray` = { actor_id, actor_name, task_id, task_name, comment_id, excerpt, created_at }

## 2. Live broadcast event

- [x] 2.1 Create `App\Domain\Comments\Events\CommentNotificationBroadcast` implementing `ShouldBroadcastNow`; `broadcastOn` → `PrivateChannel('comments.notifications')`; `broadcastAs` → `comment.notification`; `broadcastWith` = same payload as the notification
- [x] 2.2 Add `comments.notifications` channel in `routes/channels.php`, authorized for any authenticated user

## 3. Listener

- [x] 3.1 Create `App\Domain\Comments\Listeners\NotifyUsersOfComment` on `CommentCreated`: chunk-notify all users except the author (`database`), and `broadcast(new CommentNotificationBroadcast(...))`
- [x] 3.2 Register the listener via `Event::listen` in `AppServiceProvider::boot()`

## 4. Notification API

- [x] 4.1 `NotificationController@index` — current user's notifications (paginated) + `unread_count`
- [x] 4.2 `NotificationController@markRead` — `POST /api/notifications/{id}/read` (only own)
- [x] 4.3 `NotificationController@markAllRead` — `POST /api/notifications/read-all`
- [x] 4.4 Register routes under `auth:sanctum`

## 5. Frontend

- [x] 5.1 `composables/useNotifications.js`: reactive `items` + `unreadCount`; `fetch`, `markRead`, `markAllRead`; `subscribe()` to `comments.notifications` (`.comment.notification`)
- [x] 5.2 Navbar bell in `App.vue` with unread badge + dropdown list; clicking an item marks read and routes to the task
- [x] 5.3 Toast region in `App.vue` showing live incoming notifications (skip when actor is current user)
- [x] 5.4 Subscribe on auth load; refresh list + count on live event; keep TaskShow comment auto-append working

## 6. Tests

- [x] 6.1 Test: posting a root comment notifies all other users (DB), not the author
- [x] 6.2 Test: posting a reply notifies no one
- [x] 6.3 Test: `CommentNotificationBroadcast` channel/`broadcastAs`/payload; broadcast dispatched on root comment
- [x] 6.4 Test: notification API — index + unread_count, mark one read decrements, mark all read zeroes; per-user isolation
- [x] 6.5 Test: `comments.notifications` channel authorizes authenticated, rejects guest

## 7. Finalize

- [x] 7.1 `vendor/bin/pint --dirty --format agent`
- [x] 7.2 `npm run build`
- [x] 7.3 Run notification tests + full suite; ask user for a live two-session check (`reverb:start` already running)
