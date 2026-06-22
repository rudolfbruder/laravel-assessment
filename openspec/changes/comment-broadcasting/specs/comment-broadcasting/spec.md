## ADDED Requirements

### Requirement: New comments broadcast on a private task channel

When a root comment is created, the system SHALL broadcast a `comment.created` event on the private channel `tasks.{taskId}.comments`, carrying the comment serialized like the REST API (id, body, parent_id, author id/name, replies_count, created_at).

#### Scenario: Root comment is broadcast

- **WHEN** a user posts a root comment on task `{taskId}`
- **THEN** a `comment.created` broadcast is emitted on private channel `tasks.{taskId}.comments` with the comment payload

#### Scenario: Payload omits sensitive fields

- **WHEN** a comment is broadcast
- **THEN** the payload includes the author's id and name and excludes the author's email

### Requirement: New replies broadcast on their task channel

When a reply is created, the system SHALL broadcast a `reply.created` event on the private channel `tasks.{taskId}.comments` of the task the parent belongs to, carrying the reply payload including its `parent_id`.

#### Scenario: Reply is broadcast on the parent's task channel

- **WHEN** a user posts a reply to a root comment on task `{taskId}`
- **THEN** a `reply.created` broadcast is emitted on private channel `tasks.{taskId}.comments` with the reply payload and its `parent_id`

### Requirement: Task comment channel authorization

The private channel `tasks.{taskId}.comments` SHALL be authorized for any authenticated user (matching the open comment-access model) and SHALL reject unauthenticated subscribers.

#### Scenario: Authenticated user authorized

- **WHEN** an authenticated user requests authorization for `tasks.{taskId}.comments`
- **THEN** authorization succeeds

#### Scenario: Unauthenticated subscriber rejected

- **WHEN** an unauthenticated client requests authorization for the channel
- **THEN** authorization is denied

### Requirement: Live update of the task detail view

The task detail page SHALL subscribe to its task's comment channel and reflect incoming comments and replies without a reload, de-duplicating against entries the client already shows (e.g. the author's own optimistic insert).

#### Scenario: Viewer sees a new comment in real time

- **WHEN** another user posts a comment on the open task
- **THEN** the comment appears at the top of the viewer's comment list without reloading

#### Scenario: Viewer sees a new reply in real time

- **WHEN** another user posts a reply to a visible root comment
- **THEN** the reply appears under that comment and its reply count increases, without reloading

#### Scenario: No duplicate for the author

- **WHEN** the author posts a comment (already added optimistically) and the broadcast for it arrives
- **THEN** the comment is not shown twice
