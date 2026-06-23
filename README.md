# XFlow CRM

XFlow is a **lead-management and demo-website sales pipeline** built on Laravel 12.
Sales teams capture business leads, admins assign developers to build demo
websites, developers track the build, and sales drive each demo through
follow-up to conversion. Every lead carries a demo workflow, a demo lifecycle
(live/offline/deleted), a full activity timeline, file assets, in-app
notifications and conversion analytics.

## Tech stack

- **Backend:** PHP 8.2+, Laravel 12, server-rendered Blade
- **Frontend:** Vite, TailwindCSS, Alpine.js
- **Database:** MySQL (Eloquent ORM); tests run on in-memory SQLite
- **Queue / Cache / Session:** `database` driver (Redis-ready)
- **Auth:** session auth + custom `role:` middleware (Super Admin, Leads Admin, Sales, Developer)

## Quick start (local)

```bash
cp .env.example .env
composer install && npm install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
composer run dev          # server + queue + logs + vite
```

Seeded accounts use the password `Password` (e.g. `admin@xflow.com`). See
[`docs/ENVIRONMENT_SETUP.md`](docs/ENVIRONMENT_SETUP.md) for the full list.

## Roles

| Role | Scope |
|------|-------|
| Super Admin | Full access incl. user management, system status, force-delete demos |
| Leads Admin | Lead CRUD, assign developers, sales + demo-status updates |
| Sales | Lead CRUD, demo-sent / follow-up / convert / reject |
| Developer | Only assigned leads; updates demo build + uploads assets |

## Operations

- **Health probe:** `GET /health` (JSON, for uptime monitors)
- **System status:** `/system/status` (Super Admin only)
- **Backups:** `php artisan db:backup` — see [`docs/BACKUP.md`](docs/BACKUP.md)
- **Tests:** `php artisan test`

## Documentation

- [Deployment guide](docs/DEPLOYMENT.md)
- [Environment setup](docs/ENVIRONMENT_SETUP.md)
- [Production checklist](docs/PRODUCTION_CHECKLIST.md)
- [Backup & restore](docs/BACKUP.md)
- [Phase 8 hardening summary](PHASE8_SUMMARY.md)
- [Database schema](DATABASE.md) · [Project memory](PROJECT_MEMORY.md)
