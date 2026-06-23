# XFlow — Environment Setup

## Local development

```bash
git clone <repo> xflow && cd xflow
cp .env.example .env
composer install
php artisan key:generate
npm install

# Database: create a local MySQL schema named `xflow` (or adjust DB_* in .env),
# then build + seed it:
php artisan migrate:fresh --seed
php artisan storage:link

# Run everything (server + queue + logs + vite) in one command:
composer run dev
# ...or individually:
php artisan serve
php artisan queue:listen
npm run dev
```

Default seeded accounts (password `Password` for all):

| Role        | Email                  |
|-------------|------------------------|
| Super Admin | `admin@xflow.com`      |
| Leads Admin | `leads@xflow.com`      |
| Sales       | `sales@xflow.com`      |
| Developer   | `developer@xflow.com`  |

## Environment variables that matter

| Key | Local | Production |
|-----|-------|-----------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | **`false`** |
| `APP_URL` | `http://localhost` | real HTTPS URL |
| `DB_CONNECTION` | `mysql` | `mysql` |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stack` / `debug` | `daily` / `error` |
| `QUEUE_CONNECTION` | `database` | `database` (worker required) |
| `CACHE_STORE` | `database` | `database` (Redis optional) |
| `MAIL_MAILER` | `log` | `smtp` |
| `SESSION_SECURE_COOKIE` | unset | `true` |

> Tests run on an in-memory SQLite database with array cache and sync queue
> (`phpunit.xml`) — no MySQL/Redis needed to run `php artisan test`.

## Scaling note (cache & queue)

XFlow ships on the `database` driver for cache and queue, which needs no extra
infrastructure. To scale, set `CACHE_STORE=redis` and `QUEUE_CONNECTION=redis`
once a Redis server is available — the redis stores are already defined in
`config/cache.php` and `config/queue.php`, and **no application code changes are
required** (caching goes through the `Cache` facade / `StatsCache`).
