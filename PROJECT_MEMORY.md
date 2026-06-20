# XFlo — Project Memory

_Audit date: 2026-06-17. Source: full read of `app/`, `database/`, `routes/`, `config/`, `bootstrap/`, and root config files. No code was modified._

## Project Overview

XFlo is a **lead-management and demo-website sales pipeline** built on Laravel. Sales teams capture business leads, admins assign developers to build demo websites, developers track the build, and sales drives each demo through follow-up to conversion. Each lead carries three independent state machines — a legacy **sales pipeline status**, a **demo workflow status**, and a **demo lifecycle** (live/offline/deleted) — plus a full timeline of events and uploaded file assets.

## Tech Stack

- **Backend:** PHP ^8.2, Laravel ^12.0, server-rendered Blade.
- **Frontend:** Vite 7, TailwindCSS 4, Alpine.js 3, Axios.
- **Auth:** Laravel session auth (custom `LoginController`); no API/token layer.
- **Authorization:** Custom `role:` middleware (`RoleMiddleware`, aliased in `bootstrap/app.php`) + per-action Form Request `authorize()`.
- **DB:** MySQL in active `.env` (`xflow`); SQLite in `.env.example`. Eloquent ORM.
- **Storage:** `public` local disk via `storage:link` for lead assets (S3 disk configured but unused).
- **Queue/Cache/Session:** `database` driver. **Mail:** `log` driver.
- **Dev tooling:** Pint, Pail, Sail, PHPUnit ^11, Collision, Mockery, Faker.

## User Roles

Stored in `users.role`; `is_active` gates login (inactive users are logged back out).

| Role | Key | Scope |
|------|-----|-------|
| Super Admin | `super_admin` | Full access incl. user management, force-delete demos |
| Leads Admin | `leads_admin` | Lead CRUD, assign developers, sales + demo-status updates, force-delete |
| Sales | `sales` | Lead CRUD, demo-sent / follow-up / convert / reject |
| Developer | `developer` | Sees **only assigned leads** (`scopeVisibleTo`); updates demo build + uploads assets |

## Business Workflow

1. **Capture** — Lead created (`status=new`, `workflow_status=new_lead`); logs `created` event.
2. **Assign** — Admin assigns developer → `assigned`; logs `assigned`. (Two assignment paths exist: Phase 5 `leads.developer_id` and Phase 3 `developer_tasks` record.)
3. **Build** — Developer advances `demo_in_progress` → `demo_ready` (stamps `demo_created_at`), sets demo URL/notes.
4. **Send & follow up** — Sales sets `demo_sent` (stamps `demo_sent_at`) → `follow_up` → `converted`/`rejected`. Each logs a typed event.
5. **Demo lifecycle** — Independent of sales workflow: `live` ⇄ `offline` (reason required, stamps `offline_at`); admin force-delete → `deleted` (stamps `deleted_at_demo`).

## Database Summary

Tables: `users`, `password_reset_tokens`, `sessions`, `leads`, `lead_assets`, `developer_tasks`, `lead_events`, plus framework `cache`/`jobs`. See **DATABASE.md** for full schema. Cascade deletes from `leads` to assets/tasks/events; `nullOnDelete` on user FKs.

## Completed Features

- Session auth with active-account enforcement; role middleware.
- Lead CRUD + rich search/filter (status, website, workflow, developer, date range), pagination, **bulk delete**.
- Role-aware dashboard widgets.
- Lead detail page: business info, grouped assets, developer workflow, timeline.
- Phase 3 developer tasks (build status, deployment platform/date).
- Phase 5 demo workflow with event logging + timestamps.
- Phase 5.1 demo lifecycle (offline/reactivate/force-delete).
- Asset upload/download/delete (multi-file, typed).
- User management (Super Admin): CRUD + active toggle, with self-action guards.
- Seeders: default accounts per role + 8 sample leads across all stages.

## Pending / Gaps (inferred)

- Two parallel status systems (`status` vs `workflow_status`) — legacy pipeline largely unused; needs reconciliation.
- Two parallel developer-assignment mechanisms (`developer_id` vs `developer_tasks`) — overlapping/confusing.
- No notifications/email despite mail config (driver is `log`).
- No reporting/export beyond dashboard counters.
- README is still Laravel boilerplate.
- No automated tests written (only framework scaffolding).

## Roadmap (suggested)

1. Replace boilerplate README; document setup + roles.
2. Reconcile dual status + dual assignment models; pick one source of truth.
3. Add notifications (assignment, follow-up reminders).
4. Add conversion-funnel reporting (data already timestamped).
5. Harden security (see **SECURITY_REVIEW.md**) before any production deploy.
6. Add feature/unit test coverage for workflow transitions and authorization.

## Companion Documents

`DATABASE.md` · `API_DOCUMENTATION.md` · `TECHNICAL_DEBT.md` · `SECURITY_REVIEW.md` · `DEPLOYMENT_GUIDE.md`
