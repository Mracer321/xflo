# XFlow — Deployment Guide

Production deployment steps for the XFlow CRM (Laravel 12, PHP 8.2+, MySQL).

## 1. Server requirements

- PHP **8.2+** with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`.
- **MySQL 8.0+** (or MariaDB 10.6+).
- **Composer 2**, **Node.js 18+** / npm (for the front-end build).
- A web server (Nginx/Apache) with the document root at `public/`.
- `mysqldump` / `mysql` client tools on the `PATH` (for backups/restore).
- A process supervisor (Supervisor or systemd) for the queue worker.
- Cron for the task scheduler.

## 2. First-time install

```bash
git clone <repo> xflow && cd xflow

cp .env.production.example .env        # then edit real values
composer install --no-dev --optimize-autoloader
php artisan key:generate               # only if APP_KEY is empty

npm ci && npm run build                # build CSS/JS assets

php artisan migrate --force            # run DB migrations (no prompt)
php artisan db:seed --force            # OPTIONAL: only for a brand-new install
php artisan storage:link               # public symlink for lead assets
```

## 3. Optimise caches (run on every deploy, after pulling code)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> After changing `.env` you must re-run `php artisan config:cache` (or `config:clear`).

## 4. Queue worker (required — notifications & emails are queued)

Run a persistent worker. Example **Supervisor** program:

```ini
[program:xflow-worker]
command=php /var/www/xflow/artisan queue:work --tries=3 --backoff=30 --max-time=3600
directory=/var/www/xflow
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/xflow/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start xflow-worker
```

Operating the queue:

```bash
php artisan queue:failed       # list failed jobs
php artisan queue:retry all    # retry all failed jobs
php artisan queue:flush        # delete all failed jobs
```

> After each deploy, restart the worker so it picks up new code: `php artisan queue:restart`.

## 5. Task scheduler (follow-up reminders + nightly backup)

Add **one** cron entry:

```cron
* * * * * cd /var/www/xflow && php artisan schedule:run >> /dev/null 2>&1
```

This drives `leads:send-follow-up-reminders` (hourly) and `db:backup` (02:30 daily) — see `routes/console.php`.

## 6. Deploy (subsequent releases)

```bash
php artisan down                      # maintenance mode (renders errors/503)
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
```

## 7. Rollback

```bash
php artisan down
git checkout <previous-tag>
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback --step=1   # only if the release added migrations
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
```

## 8. Post-deploy verification

- `GET /health` returns `200` with `{"status":"ok"}`.
- Log in as Super Admin → **System Status** page is all green.
- `APP_DEBUG=false` (confirm on the status page).
- Worker is running (`supervisorctl status xflow-worker`).
- See `docs/PRODUCTION_CHECKLIST.md` for the full go-live list.
