## ADDED Requirements

### Requirement: Authenticated users can comment on a task

Any authenticated user SHALL be able to post a comment on any task via `POST /api/tasks/{task}/comments`. The comment SHALL record the authenticated user as its author and a creation timestamp. A successful post SHALL return `201` with the created comment serialized through `CommentResource`. Unauthenticated requests SHALL receive `401`.

#### Scenario: Posting a comment succeeds

- **WHEN** an authenticated user posts `{ "body": "Looks good" }` to `/api/tasks/{task}/comments`
- **THEN** the response is `201` and the comment is persisted with the user as author, the given task, and a timestamp

#### Scenario: Any authenticated user may comment regardless of task ownership

- **WHEN** an authenticated user who does not own the task posts a valid comment
- **THEN** the response is `201` and the comment is created

#### Scenario: Unauthenticated request is rejected

- **WHEN** an unauthenticated request posts to `/api/tasks/{task}/comments`
- **THEN** the response is `401`

### Requirement: Comment body validation

The `body` field SHALL be required, a string, trimmed, at least 1 character after trimming, at most 2000 characters, and SHALL NOT be only whitespace. Invalid input SHALL return `422` with validation errors keyed by field.

#### Scenario: Missing body rejected

- **WHEN** an authenticated user posts a comment with no `body`
- **THEN** the response is `422` with a validation error on `body`

#### Scenario: Whitespace-only body rejected

- **WHEN** an authenticated user posts `{ "body": "   " }`
- **THEN** the response is `422` with a validation error on `body`

#### Scenario: Over-length body rejected

- **WHEN** an authenticated user posts a `body` longer than 2000 characters
- **THEN** the response is `422` with a validation error on `body`

### Requirement: Replies are one level deep

A comment MAY reply to a root comment by including `parent_id`. `parent_id` SHALL reference an existing comment that belongs to the same task and is itself a root comment (its own `parent_id` is null). A reply to a non-root comment, a comment from another task, or a non-existent comment SHALL return `422`.

#### Scenario: Reply to a root comment succeeds

- **WHEN** an authenticated user posts `{ "body": "Agreed", "parent_id": <rootCommentId> }` to the same task
- **THEN** the response is `201` and the comment is stored as a reply to that root comment

#### Scenario: Reply to a reply is rejected

- **WHEN** an authenticated user posts a comment whose `parent_id` is itself a reply (non-root)
- **THEN** the response is `422` with a validation error on `parent_id`

#### Scenario: Reply parent must belong to the same task

- **WHEN** an authenticated user posts a comment to task A whose `parent_id` belongs to task B
- **THEN** the response is `422` with a validation error on `parent_id`

### Requirement: Comments are listed with author, timestamp, and reply preview

`GET /api/tasks/{task}/comments` SHALL return the task's root comments, newest activity first or chronological per implementation, paginated. Each comment SHALL include its author (id and name), creation timestamp, total `replies_count`, and a first page of its replies. Replies SHALL be fetchable on demand via `GET /api/comments/{comment}/replies`, paginated.

#### Scenario: Root comments include author and counts

- **WHEN** an authenticated user requests `GET /api/tasks/{task}/comments`
- **THEN** each returned comment includes author id and name, a timestamp, a `replies_count`, and a (possibly empty) first page of replies

#### Scenario: Additional replies fetched lazily

- **WHEN** a root comment has more replies than the first page and the user requests `GET /api/comments/{comment}/replies?page=2`
- **THEN** the response returns the next page of that comment's replies

### Requirement: Authors can delete their own comments

`DELETE /api/comments/{comment}` SHALL delete the comment only when the authenticated user is its author, returning `204`. Deleting a root comment SHALL cascade-delete its replies. A non-author SHALL receive `403`.

#### Scenario: Author deletes own comment

- **WHEN** the comment's author sends `DELETE /api/comments/{comment}`
- **THEN** the response is `204` and the comment (and any replies) are removed

#### Scenario: Non-author cannot delete

- **WHEN** an authenticated user who is not the author sends `DELETE /api/comments/{comment}`
- **THEN** the response is `403` and the comment remains

### Requirement: Comment and reply creation dispatch domain events

Posting a root comment SHALL dispatch a `CommentCreated` event carrying the comment; posting a reply SHALL dispatch a `ReplyCreated` event carrying the reply. These events SHALL be dispatched on successful persistence and are intended for a later broadcasting stage.

#### Scenario: Root comment dispatches CommentCreated

- **WHEN** an authenticated user posts a root comment
- **THEN** a `CommentCreated` event is dispatched with the created comment

#### Scenario: Reply dispatches ReplyCreated

- **WHEN** an authenticated user posts a reply
- **THEN** a `ReplyCreated` event is dispatched with the created reply
