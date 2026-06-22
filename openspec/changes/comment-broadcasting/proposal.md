## Why

Comments and replies already dispatch `CommentCreated` / `ReplyCreated` domain events, but they are plain events — a second user viewing a task does not see new activity until reloading. This stage wires those events to Laravel Reverb so comments and replies appear in real time over WebSockets. The Reverb server, env, and JS client deps are already installed.

## What Changes

- `CommentCreated` and `ReplyCreated` implement `ShouldBroadcastNow`, broadcasting on a private per-task channel `tasks.{taskId}.comments`.
- Each event defines `broadcastAs` (`comment.created` / `reply.created`) and `broadcastWith` (the comment serialized via `CommentResource`, author + reply count loaded).
- Channel authorization in `routes/channels.php`: `tasks.{taskId}.comments` authorized for any authenticated user (matches the open comment-access model). Broadcasting auth route registered (`bootstrap/app.php` `withBroadcasting`).
- Frontend: initialize Laravel Echo (Reverb broadcaster) using the `VITE_REVERB_*` env, authenticating the private channel with the Sanctum Bearer token. `TaskShow.vue` subscribes to its task channel and merges incoming `comment.created` (prepend root) and `reply.created` (append under parent, bump count) into the live list, de-duplicating by `id` so the author's own optimistic insert is not duplicated.

## Capabilities

### New Capabilities
- `comment-broadcasting`: Real-time delivery of new comments and replies to clients viewing a task, via Reverb private channels.

### Modified Capabilities
- `task-comments`: comment/reply creation now also broadcasts (the existing create behavior and events are unchanged; broadcasting is additive).

## Impact

- **Backend**: `app/Domain/Comments/Events/{CommentCreated,ReplyCreated}.php` (implement `ShouldBroadcastNow`, `broadcastOn/As/With`), `routes/channels.php` (channel auth), `bootstrap/app.php` (register broadcasting routes if not already).
- **Frontend**: `resources/js/bootstrap.js` (Echo init), `resources/js/pages/TaskShow.vue` (subscribe + merge), `resources/js/composables/useEcho.js` (optional helper).
- **Config/deps**: already present — `laravel/reverb`, `laravel-echo`, `pusher-js`, `@laravel/echo-vue`, `BROADCAST_CONNECTION=reverb`, `REVERB_*` / `VITE_REVERB_*`.
- **Tests**: assert events are broadcastable, target the correct channel, and carry the expected payload; channel authorization returns true for authenticated users.
- **Out of scope**: presence/typing indicators, read receipts, broadcasting task changes, notification fan-out.
