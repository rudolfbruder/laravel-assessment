## Why

Real-time comment delivery exists on the task page, but a user not currently viewing a task has no idea someone commented. This adds app-wide notifications: when a top-level comment is posted, every other authenticated user gets a live, informative notice (who commented, on which task) plus a persistent bell with unread count — without refreshing.

## What Changes

- When a **root comment** is created (replies excluded), notify **all authenticated users except the author**.
- **Two delivery paths** (chosen to avoid a queue worker, since `QUEUE_CONNECTION=database`):
  - **Live toast**: a `ShouldBroadcastNow` event `CommentNotificationBroadcast` on a single shared private channel `comments.notifications`, carrying actor name, task id/name, comment id, and a body excerpt. One broadcast reaches all viewers; each client ignores its own action.
  - **Persistent bell**: a Laravel **database** notification (`NewCommentNotification`) written (synchronously, chunked) to every other user, powering a bell with unread count, list, and mark-as-read.
- A `NotifyUsersOfComment` listener on the existing `CommentCreated` event does both (registered explicitly — domain path).
- API for the bell: list current user's notifications, unread count, mark one / mark all read.
- Frontend: global bell + dropdown in the authenticated navbar, a transient toast, and a `useNotifications` composable that subscribes to `comments.notifications` (live toast + refresh) and to the user's notification list.
- The comment list on the open task already auto-updates (prior change); this change keeps that and adds the cross-page notification layer.

## Capabilities

### New Capabilities
- `comment-notifications`: App-wide real-time + persisted notifications to other users when a task receives a new top-level comment.

### Modified Capabilities
- `comment-broadcasting`: a new shared notification channel/event is added alongside the per-task comment channel (additive; existing task-channel behavior unchanged).

## Impact

- **Backend**: `app/Domain/Comments/Events/CommentNotificationBroadcast.php`, `app/Domain/Comments/Notifications/NewCommentNotification.php`, `app/Domain/Comments/Listeners/NotifyUsersOfComment.php` (registered in `AppServiceProvider`), `routes/channels.php` (`comments.notifications`), `app/Http/Controllers/Api/NotificationController.php` + routes, `notifications` table migration.
- **Frontend**: `resources/js/composables/useNotifications.js`, a bell component + toast in `App.vue`.
- **Tests**: listener notifies others (not actor), only on root comments; broadcast event channel/payload; notification API (list/unread/mark read); channel auth.
- **Out of scope**: email/SMS channels, notification preferences, per-task muting, notifications for replies or task changes.
