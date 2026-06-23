# XFlow — Backup & Restore

XFlow has two pieces of state to protect:

1. **Database** (`xflow` MySQL schema) — all leads, users, timeline, notifications.
2. **Uploaded files** — `storage/app/public/lead-assets/**` (logos, images, docs).

## Database backup

A built-in artisan command wraps `mysqldump`:

```bash
php artisan db:backup            # writes storage/app/backups/xflow_<timestamp>.sql
php artisan db:backup --keep=14  # retain the 14 most-recent dumps (default 7)
```

It is scheduled nightly at **02:30** via `routes/console.php` (needs the
scheduler cron — see `docs/DEPLOYMENT.md`). Requires the `mysqldump` client tool
on the `PATH`.

Manual dump (equivalent, if you prefer raw tooling):

```bash
mysqldump --single-transaction --routines --no-tablespaces \
  -u <user> -p xflow > xflow_backup.sql
```

## File (storage) backup

```bash
# Linux
tar czf xflow_storage_$(date +%F).tar.gz -C storage/app public

# Windows (PowerShell)
Compress-Archive -Path storage\app\public -DestinationPath xflow_storage.zip
```

> Copy both the `.sql` dump and the storage archive **off the server** (object
> storage / another host). Backups under `storage/app/backups` live on the same
> machine and are not a disaster-recovery copy on their own.

## Restore

**Database:**

```bash
# Create the schema if it doesn't exist, then load the dump.
mysql -u <user> -p -e "CREATE DATABASE IF NOT EXISTS xflow;"
mysql -u <user> -p xflow < storage/app/backups/xflow_<timestamp>.sql
```

**Files:**

```bash
# Linux
tar xzf xflow_storage_<date>.tar.gz -C storage/app

# Windows (PowerShell)
Expand-Archive -Path xflow_storage.zip -DestinationPath storage\app
```

Then ensure the public symlink exists:

```bash
php artisan storage:link
```

## Verify a restore

- `php artisan migrate:status` shows all migrations as run.
- Log in and confirm leads, timeline events and uploaded assets are present.
- `GET /health` returns `200`.
