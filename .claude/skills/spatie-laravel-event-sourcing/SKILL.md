---
name: spatie-event-sourcing
description: Implement event sourcing in Laravel with the spatie/event-sourcing package — design domain events, aggregate roots, projectors, reactors, per-domain stored-event repositories, and refactor CRUD into event-sourced flows. Use when working with spatie/event-sourcing, aggregate roots, projectors, reactors, stored events, or extracting existing Laravel logic into an event-sourced / CQRS / DDD design.
---

# Spatie Event Sourcing (Laravel)

Expert guidance for building and refactoring event-sourced flows with `spatie/event-sourcing`. Covers DDD bounded contexts, CQRS, events, aggregate roots, projectors, reactors, and per-domain stored-event repositories.

## First step: verify the API

Never rely on memory for package API details — they change between major versions.

1. If a `search-docs` (or equivalent docs) tool is available, query it first: `stored events`, `aggregate roots`, `projectors`, `reactors`, `event serialization`.
2. Otherwise check the installed version (`composer show spatie/event-sourcing`) and read `vendor/spatie/event-sourcing` or the docs at https://spatie.be/docs/laravel-event-sourcing.
3. Then read the existing code you're changing before proposing anything.

## Core concepts

| Component | Base class | Job |
|-----------|-----------|-----|
| Event | `Spatie\EventSourcing\StoredEvents\ShouldBeStored` | Immutable past-tense fact |
| Aggregate root | `Spatie\EventSourcing\AggregateRoots\AggregateRoot` | Enforce invariants, record events |
| Projector | `Spatie\EventSourcing\EventHandlers\Projectors\Projector` | Build read models (idempotent) |
| Reactor | `Spatie\EventSourcing\EventHandlers\Reactors\Reactor` | Side effects (not replayed) |

### Events
- Past tense, describes what happened: `OrderPlaced`, `ProductAddedToCart`, `PaymentReceived`.
- Immutable. Never change an existing event's structure in a way that breaks deserialization of historical rows.
- New properties → nullable with defaults.
- Store only minimal data needed to reconstruct state. Explicit PHP types on every property.
- Namespace per domain: `App\Domain\{Context}\Events\`.

### Aggregate roots
- Public methods = commands: validate, then record events. `apply*` methods mutate in-memory state only — no side effects.
- One bounded context per aggregate. UUID identity.
- Namespace: `App\Domain\{Context}\Aggregates\`.

### Projectors
- Build read models from events. Must be idempotent — replay must produce identical state. Use `updateOrCreate` / `upsert`.
- `onEvent*` methods or `__invoke` with a type-hinted event param.
- For new read models, extend the package `Projection` class.
- Namespace: `App\Domain\{Context}\Projectors\`.

### Reactors
- One-time side effects: email, jobs, notifications, external APIs. Not replayed.
- Namespace: `App\Domain\{Context}\Reactors\`.

## Directory convention

Follow the project's existing structure if one exists. Otherwise:

```
app/Domain/{BoundedContext}/
  Aggregates/
  Events/
  Projectors/
  Reactors/
  Data/
```

## Per-domain stored events (important)

This pattern assumes each domain owns its own stored-events table + repository — **not** the default `StoredEvent` model / `stored_events` table.

- Override `getStoredEventRepository()` in every aggregate root:

```php
protected function getStoredEventRepository(): OrderStoredEventsRepository
{
    return app(OrderStoredEventsRepository::class);
}
```

- Before creating a new repository, check whether an existing domain repo can be reused.
- New repository → also create the migration for its `{domain}_stored_events` table. Copy schema from an existing one (e.g. `order_stored_events`).

## Refactoring CRUD → event sourcing

| CRUD | Event-sourced |
|------|---------------|
| `Model::create($data)` | aggregate command → records `EntityCreated` → projector creates model |
| `$model->update($data)` | aggregate command → records `EntityUpdated` → projector updates model |
| `$model->delete()` | aggregate command → records `EntityDeleted` → projector deletes/soft-deletes |

Side effects (email, jobs, notifications, API calls) → move into reactors, not the aggregate.

## Reactor database rule

Reactors may call `EmailService` and create email models. **Other DB writes do not belong in a reactor** — instead dispatch a new event from the reactor that a dedicated projector listens to. Keeps read-model state rebuildable via replay.

## Spatie Data integration

When events carry complex data, use Spatie Data objects as event properties. Serialize with the Data class (`$data->toJson()`), **never** `json_encode()` on a raw array — key casing won't match and you get silent data loss.

## Code quality

- PHP 8.4: constructor property promotion, named args, enums, match. Explicit return + param types. PHPDoc for complex array shapes.
- Follow the project's Laravel conventions / CLAUDE.md.
- Use `php artisan make:` generators where available.
- Run `vendor/bin/pint --dirty` before finishing.

## Testing (Pest)

- Aggregate: call command method, assert recorded events.
- Projector: fire event, assert DB state.
- Reactor: fire event, assert side effect via mock/fake.

## Warnings

- Don't break deserialization of historical events.
- Check if a `stored_events` migration already exists before creating one.
- Don't mix aggregate-root writes with direct Eloquent writes for the same entity.
- Projectors must survive replay — `updateOrCreate` / `upsert`, never blind `create`.
