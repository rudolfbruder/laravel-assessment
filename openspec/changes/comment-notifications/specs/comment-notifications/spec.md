## ADDED Requirements

### Requirement: Other users are notified of a new top-level comment

When a root comment is created, the system SHALL notify every authenticated user except the comment's author. Replies SHALL NOT trigger notifications. Each notification SHALL identify the actor (name), the task (id and name), and the comment (id and a body excerpt).

#### Scenario: Other users receive a notification

- **WHEN** a user posts a root comment on a task
- **THEN** every other authenticated user receives a notification identifying the actor, task, and comment

#### Scenario: The author is not notified

- **WHEN** a user posts a root comment
- **THEN** that user does not receive a notification for their own comment

#### Scenario: Replies do not notify

- **WHEN** a user posts a reply to a comment
- **THEN** no comment notification is generated

### Requirement: Live notification without refresh

A new top-level comment SHALL be delivered to other users in real time over a shared private channel, so a notification appears without reloading the page, regardless of which page the recipient is on.

#### Scenario: Toast appears live

- **WHEN** another user posts a root comment while a recipient is using the app
- **THEN** the recipient sees a notification (toast) without refreshing, showing who commented and on which task

#### Scenario: Actor sees no toast for own comment

- **WHEN** a user posts a comment
- **THEN** that user does not see a notification toast for it

### Requirement: Persistent notification bell

Notifications SHALL be persisted per user and exposed via API: the authenticated user can list their notifications, read an unread count, mark one read, and mark all read. Marking read SHALL reduce the unread count.

#### Scenario: Unread count and list

- **WHEN** an authenticated user has unread notifications and requests their notifications
- **THEN** the response includes the notifications and an unread count

#### Scenario: Mark one read

- **WHEN** the user marks a specific notification read
- **THEN** that notification is flagged read and the unread count decreases

#### Scenario: Mark all read

- **WHEN** the user marks all notifications read
- **THEN** the unread count becomes zero

### Requirement: Notification channel authorization

The shared notification channel `comments.notifications` SHALL be authorized for any authenticated user and reject unauthenticated subscribers. Notification API endpoints SHALL require authentication and operate only on the current user's notifications.

#### Scenario: Authenticated user authorized for the channel

- **WHEN** an authenticated user requests authorization for `comments.notifications`
- **THEN** authorization succeeds

#### Scenario: Notifications are per-user

- **WHEN** an authenticated user lists or marks notifications
- **THEN** only that user's notifications are returned or modified
