# XFlo — Database Schema

_Generated from `database/migrations/`, models, and seeders on 2026-06-17. Active connection: MySQL (`xflow`). `.env.example` defaults to SQLite._

## Entity Relationships

```
users 1──* leads          (leads.developer_id → users.id, nullOnDelete)   [Phase 5]
users 1──* developer_tasks (developer_tasks.developer_id, nullOnDelete)
users 1──* lead_events     (lead_events.user_id, nullOnDelete)
users 1──* lead_assets     (lead_assets.uploaded_by, nullOnDelete)

leads 1──1 developer_tasks (developer_tasks.lead_id UNIQUE, cascadeOnDelete) [Phase 3]
leads 1──* lead_assets     (cascadeOnDelete)
leads 1──* lead_events     (cascadeOnDelete)
```

> Note: A lead can be linked to a developer two ways — `leads.developer_id` (Phase 5 workflow) **and** `developer_tasks.developer_id` (Phase 3). Both are honored by `Lead::scopeVisibleTo`.

## Tables

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string | UNIQUE |
| email_verified_at | timestamp | nullable (unused; no verification flow) |
| password | string | bcrypt (hashed cast) |
| role | string | INDEX, default `sales`; one of `super_admin\|leads_admin\|sales\|developer` |
| is_active | boolean | default `true` (added in later migration) |
| remember_token | string | |
| timestamps | | |

### `leads`
Built across three migrations (create + Phase 5 workflow + Phase 5.1 lifecycle).
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| business_name | string | INDEX |
| owner_name | string | nullable |
| mobile_number | string | nullable |
| whatsapp_number | string | nullable |
| email | string | nullable |
| category | string | nullable, INDEX |
| address | text | nullable |
| google_business_url | string | nullable |
| website_exists | boolean | default false |
| facebook_url / instagram_url | string | nullable |
| status | string | INDEX, default `new` — legacy sales pipeline (10 values) |
| notes | text | nullable |
| **workflow_status** | string | INDEX, default `new_lead` — demo workflow (8 values) |
| **developer_id** | FK→users | nullable, nullOnDelete |
| demo_url | string | nullable |
| demo_created_at / demo_sent_at | timestamp | nullable |
| demo_notes / sales_notes | text | nullable |
| **demo_status** | string | INDEX, default `live` — `live\|offline\|deleted` |
| offline_reason | text | nullable |
| offline_at / deleted_at_demo | timestamp | nullable (soft demo-delete marker; row not removed) |
| timestamps | | |

**`status` values:** new, contacted, demo_requested, demo_sent, follow_up_1, follow_up_2, interested, meeting_scheduled, won, lost.
**`workflow_status` values:** new_lead, assigned, demo_in_progress, demo_ready, demo_sent, follow_up, converted, rejected.

### `developer_tasks`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| lead_id | FK→leads | **UNIQUE**, cascadeOnDelete (one per lead) |
| developer_id | FK→users | nullable, nullOnDelete |
| status | string | INDEX, default `not_started`; `not_started\|developing\|deploying\|demo_ready\|offline\|deleted` |
| notes | text | nullable |
| demo_url | string | nullable |
| deployment_platform | string | nullable; `vercel\|netlify\|cloudflare_pages` |
| deployment_date | date | nullable |
| reason | text | nullable; required when status = offline/deleted |
| timestamps | | |

### `lead_events`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| lead_id | FK→leads | cascadeOnDelete |
| user_id | FK→users | nullable, nullOnDelete (actor) |
| type | string | INDEX; created, assigned, demo_started, demo_ready, demo_sent, follow_up, converted, rejected, note, demo_created, demo_offline, demo_reactivated, demo_deleted |
| description | text | nullable |
| timestamps | | |

### `lead_assets`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| lead_id | FK→leads | cascadeOnDelete |
| file_name | string | original display name |
| file_path | string | path on `public` disk (`lead-assets/{lead_id}/…`) |
| file_type | string | INDEX; `logo\|image\|document\|screenshot` |
| uploaded_by | FK→users | nullable, nullOnDelete |
| timestamps | | |

### Framework tables
`password_reset_tokens` (email PK), `sessions` (DB session driver), `cache`/`cache_locks`, `jobs`/`job_batches`/`failed_jobs`.

## Indexes
Single-column indexes on: `users.email` (unique), `users.role`, `leads.status`, `leads.workflow_status`, `leads.demo_status`, `leads.business_name`, `leads.category`, `developer_tasks.lead_id` (unique) + `status`, `lead_events.type`, `lead_assets.file_type`. No composite indexes — see TECHNICAL_DEBT.md (filtered list queries combine several columns).

## Seed Data
`UserSeeder`: super admin `admin@xflow.com`, plus `leads@`, `sales@`, `developer@`, and sample users incl. one inactive — **all password `Password`**. `DemoWorkflowSeeder`: 8 sample leads spanning every workflow stage with timelines.
