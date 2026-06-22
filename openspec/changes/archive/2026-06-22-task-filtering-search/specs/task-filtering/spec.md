## ADDED Requirements

### Requirement: Search tasks by name

The task index endpoint (`GET /api/tasks`) SHALL accept an optional `search` query parameter and return only the authenticated user's tasks whose `name` contains the given value, matched case-insensitively as a partial (substring) match. When `search` is absent, empty, or whitespace-only, no name filtering SHALL be applied.

#### Scenario: Matching tasks returned

- **WHEN** an authenticated user requests `GET /api/tasks?search=report` and owns tasks named "Quarterly report" and "Buy milk"
- **THEN** the response contains "Quarterly report" and excludes "Buy milk"

#### Scenario: Case-insensitive partial match

- **WHEN** an authenticated user requests `GET /api/tasks?search=REPORT` and owns a task named "Quarterly report"
- **THEN** the response contains "Quarterly report"

#### Scenario: Empty search returns all tasks

- **WHEN** an authenticated user requests `GET /api/tasks?search=` 
- **THEN** the response contains all of the user's tasks, ordered as in the unfiltered index

### Requirement: Filter tasks by status

The task index endpoint SHALL accept an optional `status` query parameter and return only tasks whose `status` equals the given value. Valid values are `todo`, `in_progress`, and `done`. When `status` is absent, empty, or `all`, no status filtering SHALL be applied. An invalid `status` value SHALL produce a `422` validation error.

#### Scenario: Filter to a single status

- **WHEN** an authenticated user requests `GET /api/tasks?status=done`
- **THEN** the response contains only tasks with status `done`

#### Scenario: Status "all" disables the filter

- **WHEN** an authenticated user requests `GET /api/tasks?status=all`
- **THEN** the response contains tasks of every status

#### Scenario: Invalid status rejected

- **WHEN** an authenticated user requests `GET /api/tasks?status=archived`
- **THEN** the response status is `422`

### Requirement: Search and status filters compose

When both `search` and `status` parameters are present, the endpoint SHALL return only tasks that satisfy both conditions (logical AND). Filtering SHALL always be scoped to the authenticated user and SHALL preserve the existing newest-first ordering.

#### Scenario: Combined filters narrow results

- **WHEN** an authenticated user requests `GET /api/tasks?search=report&status=todo`
- **THEN** the response contains only the user's tasks that are status `todo` AND whose name contains "report"

#### Scenario: Filtering never crosses users

- **WHEN** user A requests `GET /api/tasks?search=report` and user B also owns a task named "report"
- **THEN** user A's response contains only user A's tasks

### Requirement: Configuration-selectable filter engine

Task filtering SHALL be performed through a repository contract resolved from the service container. The implementation SHALL be selected by the `tasks.filter_engine` configuration value (driven by the `TASK_FILTER_ENGINE` environment variable): `classic` uses the hand-written query implementation, `spatie` uses the `spatie/laravel-query-builder` implementation. Both implementations SHALL produce equivalent results for the same inputs.

#### Scenario: Classic engine selected

- **WHEN** `TASK_FILTER_ENGINE=classic` and a user requests `GET /api/tasks?search=report&status=todo`
- **THEN** results are produced by the classic repository and satisfy both filters

#### Scenario: Spatie engine selected

- **WHEN** `TASK_FILTER_ENGINE=spatie` and a user requests `GET /api/tasks?search=report&status=todo`
- **THEN** results are produced by the spatie repository and are equivalent to the classic engine's results
