## Context

`CommentCreated` / `ReplyCreated` are plain domain events dispatched from `CommentController@store`. Reverb is installed and configured (`BROADCAST_CONNECTION=reverb`, `REVERB_*` + `VITE_REVERB_*` env, `config/broadcasting.php`). Frontend has `laravel-echo`, `pusher-js`, `@laravel/echo-vue` (v2) installed but not yet initialized. SPA auth is Sanctum **Bearer token** in localStorage (not cookie session), which matters for private-channel auth.

## Goals / Non-Goals

**Goals:**
- Broadcast new comments/replies on a private per-task channel in real time.
- Authorize the channel for any authenticated user (open comment-access model).
- Live-merge incoming comments/replies into `TaskShow.vue`, de-duped by id.
- Tests for broadcastability, channel, payload, and channel auth.

**Non-Goals:**
- Presence/typing/read-receipts, broadcasting task mutations, notifications, queue tuning.

## Decisions

### 1. `ShouldBroadcastNow` (not queued)
Both events implement `ShouldBroadcastNow` so the broadcast fires synchronously within the request — no queue worker required in the Docker setup, and the in-memory model keeps its eager-loaded `user`/`replies_count` for the payload. Trade-off: small added request latency. Can switch to `ShouldBroadcast` later if a worker is run.

### 2. Private channel `tasks.{taskId}.comments`
Both events `broadcastOn` `new PrivateChannel("tasks.{$taskId}.comments")` (reply uses its parent's `task_id`, which equals the reply's own `task_id`). One channel per task carries both event types. Authorized in `routes/channels.php` returning `true` for any authenticated user, matching the REST rule that any authenticated user may read/post comments. Broadcasting auth routes registered via `bootstrap/app.php` `->withBroadcasting(__DIR__.'/../routes/channels.php')`.
- *Alternative:* per-comment channels for replies — rejected; one task channel is simpler and lets a viewer get all activity with one subscription.

### 3. Payload via `CommentResource`
`broadcastAs`: `comment.created` / `reply.created`. `broadcastWith` returns `(new CommentResource($comment->loadMissing('user')->loadCount('replies')))->resolve()` — identical shape to the REST API, so the frontend merge path is the same as POST responses. Never includes author email.

### 4. Echo initialization with `@laravel/echo-vue`
Call `configureEcho({ broadcaster: 'reverb', key: VITE_REVERB_APP_KEY, wsHost: VITE_REVERB_HOST, wsPort, wssPort, forceTLS: scheme==='https', enabledTransports: ['ws','wss'], auth: { headers: { Authorization: Bearer <token> } } })` once at app boot (after `./bootstrap`). The Bearer header makes `/broadcasting/auth` work with Sanctum tokens. Token read from localStorage at configure time.

### 5. Subscribe + merge in `TaskShow.vue`
Use `useEcho(\`tasks.${id}.comments\`, ['.comment.created', '.reply.created'], handler, [], 'private')` (or the echo-vue listen API). Handlers:
- `comment.created`: if no root with that id, `unshift` normalized comment (newest-first, consistent with the composer).
- `reply.created`: find parent root; if present and reply id not already in `parent.replies`, push it and bump `replies_count`.
De-dup by id means the author's optimistic insert (from the POST response, same id) is never duplicated. Leave the channel on unmount.

## Risks / Trade-offs

- **Bearer-token channel auth**: Echo must send the Authorization header; if absent, `/broadcasting/auth` 403s and the private subscribe fails silently. Mitigated by configuring `auth.headers` and surfacing connect errors to the console.
- **Token refresh / login after boot**: Echo configured at boot reads the current token; after a fresh login the app reloads (SPA), so the token is present. Acceptable.
- **`ShouldBroadcastNow` latency**: synchronous Reverb publish in the request path; negligible for this scale.
- **Reverb server must be running** (`php artisan reverb:start`) for live delivery; REST + tests work without it. Tests use `Broadcast`/`Event` fakes, not a live server.
- **Reply for a not-yet-loaded root**: if the parent isn't in the viewer's current page, the reply is dropped from the live view (will appear on reload/expand). Acceptable for one-level lazy UI.

## Migration Plan

1. Events → `ShouldBroadcastNow` + `broadcastOn/As/With`.
2. Channel auth in `routes/channels.php`; ensure broadcasting routes registered.
3. Echo init (`configureEcho`) at app boot.
4. `TaskShow.vue` subscribe + merge + cleanup.
5. Tests; `npm run build`.

Rollback: revert events to plain (drop `ShouldBroadcastNow`) and remove the subscription — REST behavior unaffected.

## Open Questions

- None blocking; channel auth is intentionally permissive to match current comment access. Tighten when task sharing/roles land.
