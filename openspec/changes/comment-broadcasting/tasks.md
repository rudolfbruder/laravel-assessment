<!-- Reverb + JS deps already installed/configured by the user. -->

## 1. Broadcastable events

- [x] 1.1 `CommentCreated` implements `ShouldBroadcastNow`; `broadcastOn` → `PrivateChannel("tasks.{task_id}.comments")`; `broadcastAs` → `comment.created`; `broadcastWith` → `CommentResource` payload (author + replies_count loaded)
- [x] 1.2 `ReplyCreated` implements `ShouldBroadcastNow`; `broadcastOn` → parent task channel; `broadcastAs` → `reply.created`; `broadcastWith` → reply `CommentResource` payload

## 2. Channel authorization

- [x] 2.1 Register broadcasting auth routes (`bootstrap/app.php` `->withBroadcasting(routes/channels.php)` if not already active)
- [x] 2.2 Add `tasks.{taskId}.comments` channel in `routes/channels.php`, authorized for any authenticated user

## 3. Frontend Echo

- [x] 3.1 Initialize Echo via `@laravel/echo-vue` `configureEcho` (reverb, `VITE_REVERB_*`, Bearer auth header) at app boot
- [x] 3.2 `TaskShow.vue`: subscribe to `tasks.{id}.comments` (private); merge `comment.created` (prepend) and `reply.created` (append + bump count), de-duped by id; leave channel on unmount

## 4. Tests

- [x] 4.1 Test: posting a root comment broadcasts `CommentCreated` on `tasks.{id}.comments` (assert event broadcast + channel + payload)
- [x] 4.2 Test: posting a reply broadcasts `ReplyCreated` on the parent's task channel with `parent_id`
- [x] 4.3 Test: channel authorization returns true for an authenticated user

## 5. Finalize

- [x] 5.1 Run `vendor/bin/pint --dirty --format agent`
- [x] 5.2 `npm run build`
- [x] 5.3 Run broadcasting tests + full suite; ask user to run a live check with `php artisan reverb:start`
