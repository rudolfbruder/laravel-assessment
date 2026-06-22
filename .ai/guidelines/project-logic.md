# Project Logic

## Overview

A single-page **task management** application. Authenticated users create, view, edit, and delete their own tasks. Backend is a Laravel 12 REST API secured with Sanctum token auth; frontend is a Vue 3 SPA that consumes the API.

This codebase is an **assessment** — a showcase of clean, best-practice Laravel + Vue work. Keep changes idiomatic and well-tested.

## Stack

| Layer | Tech |
|-------|------|
| Framework | Laravel 12 (PHP ^8.2) — **do not upgrade** |
| Auth | Laravel Sanctum ^4.0 (Bearer token, `HasApiTokens` on `User`) |
| Frontend | Vue 3.5 SPA, vue-router 5, axios |
| API style | `Route::apiResource` REST under `/api` |

## Domain Model

Two entities, one relationship.

- **User** (`app/Models/User.php`) — `Authenticatable`, `HasApiTokens`, `HasFactory`, `Notifiable`. `hasMany(Task)`. Fillable: `name`, `email`, `password`. Password `hashed` cast, hidden in serialization.
- **Task** (`app/Models/Task.php`) — `belongsTo(User)`. Fillable: `user_id`, `name`, `description`, `status`, `priority`, `due_date`. `due_date` cast to `date`.

### Task schema (`tasks` migration)
- `user_id` — FK to users, `onDelete('cascade')`
- `name` — string, required
- `description` — text, nullable
- `status` — enum `todo | in_progress | done`, default `todo`
- `priority` — enum `low | medium | high`, default `medium`
- `due_date` — date, nullable
- timestamps

## API Surface (`routes/api.php`)

**Public**
- `POST /api/register` — `AuthController@register`
- `POST /api/login` — `AuthController@login`

**Protected (`auth:sanctum`)**
- `GET /api/user` — current user (closure)
- `POST /api/logout` — `AuthController@logout`
- `apiResource('tasks', TaskController)` — index / store / show / update / destroy

### Auth flow (`AuthController`)
- **register** — validate (`password` `min:8|confirmed`, `email` unique), create user, return `{ user, token }` 201. Token via `createToken('auth_token')`.
- **login** — validate, lookup by email, `Hash::check`; on fail throw `ValidationException` "credentials are incorrect". Return `{ user, token }`.
- **logout** — delete `currentAccessToken()`.

### Task flow (`TaskController`)
- Scoped per user: `$request->user()->tasks()` for list/create.
- **index** — user's tasks, `latest()`.
- **store** — validate, create under user, 201.
- **show / update / destroy** — route-model-bound `Task`; **ownership checked inline** via `if ($task->user_id !== $request->user()->id) abort(403)`.
- Validation enums mirror the migration (`status`, `priority`). Update uses `sometimes|required`.

## Frontend (`resources/js`)

SPA mounted via `app.js` / `App.vue`.

- **Router** (`router/index.js`) — pages: TaskList `/`, Login, Register, TaskCreate `/tasks/create`, TaskShow `/tasks/:id`, TaskEdit `/tasks/:id/edit`. Route meta `auth` / `guest`; `beforeEach` guard reads `localStorage.token`.
- **`composables/useApi.js`** — axios instance `baseURL: /api`. Request interceptor attaches `Bearer` token from localStorage. Response interceptor: on **401**, clear session + redirect `/login`.
- **`composables/useAuth.js`** — reactive `token` / `user` backed by localStorage. `login` / `register` set session, `logout` calls API then clears session.

## Known Gaps / Improvement Surface

Current code is clean but minimal — likely targets for later assessment stages:

- **No Form Requests** — validation inline in controllers (duplicated between store/update).
- **No API Resources** — models returned raw as JSON (no `JsonResource` layer).
- **No Policy** — ownership enforced by inline `abort(403)` instead of `TaskPolicy` / `authorize()`.
- **No domain tests** — only stock `ExampleTest` stubs in `tests/`.
- **No pagination / filtering** on task index.
- **No backed PHP enums** — status/priority are raw strings.

## Conventions

- Follow Laravel 12 idioms; defer framework upgrades.
- Keep API responses JSON; respect existing auth scoping (users only touch their own tasks).
- Match existing style: thin controllers, Eloquent relationships, Sanctum tokens.
