## ADDED Requirements

### Requirement: Tasks serialized through a single API Resource

All task endpoints (`index`, `store`, `show`, `update`) SHALL return task data through a single `TaskResource` rather than raw model JSON. The resource SHALL expose `id`, `name`, `description`, `status`, `priority`, `due_date`, `created_at`, and `updated_at`, and SHALL NOT expose `user_id`. The serialized shape SHALL be identical regardless of which endpoint produced it.

#### Scenario: Single task returns resource shape

- **WHEN** an authenticated user requests `GET /api/tasks/{id}` for an owned task
- **THEN** the JSON contains `id`, `name`, `description`, `status`, `priority`, `due_date`, `created_at`, `updated_at` and omits `user_id`

#### Scenario: Created task returns resource shape

- **WHEN** an authenticated user creates a task via `POST /api/tasks`
- **THEN** the `201` response body is the task serialized by `TaskResource`

#### Scenario: Updated task returns resource shape

- **WHEN** an authenticated user updates an owned task via `PUT /api/tasks/{id}`
- **THEN** the response body is the task serialized by `TaskResource`

### Requirement: Index returns a resource collection

The task index endpoint SHALL return the filtered tasks as a `TaskResource` collection. Each element SHALL use the same `TaskResource` shape, ordered newest-first, after filtering is applied.

#### Scenario: Index elements use resource shape

- **WHEN** an authenticated user requests `GET /api/tasks`
- **THEN** each task in the response uses the `TaskResource` shape and omits `user_id`
