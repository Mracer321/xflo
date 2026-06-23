# XFlow — Production Go-Live Checklist

## Environment
- [ ] `.env` created from `.env.production.example`
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`  ← critical (no stack-trace leaks)
- [ ] `APP_KEY` generated (`php artisan key:generate`)
- [ ] `APP_URL` set to the real HTTPS domain
- [ ] `DB_*` point at the production MySQL with a least-privilege app user
- [ ] `LOG_CHANNEL=daily`, `LOG_LEVEL=error`
- [ ] `QUEUE_CONNECTION=database`
- [ ] `MAIL_MAILER=smtp` with real credentials
- [ ] `SESSION_SECURE_COOKIE=true`

## Build & migrate
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`
- [ ] `php artisan migrate --force`
- [ ] `php artisan storage:link`
- [ ] `php artisan config:cache && route:cache && view:cache && event:cache`

## Runtime services
- [ ] Queue worker running under Supervisor/systemd (`queue:work --tries=3`)
- [ ] Scheduler cron installed (`* * * * * php artisan schedule:run`)
- [ ] `queue:restart` wired into the deploy script

## Security
- [ ] HTTPS enforced at the web server / load balancer
- [ ] `public/` is the only web-exposed directory
- [ ] DB user is not `root`; backups stored off the web root
- [ ] File uploads limited to whitelisted types (no SVG/executables) — enforced
      in `StoreLeadAssetRequest`
- [ ] Asset download/delete is access-controlled (IDOR fix) — verified by tests

## Backups
- [ ] `mysqldump`/`mysql` client tools installed on the server
- [ ] `php artisan db:backup` succeeds and writes to `storage/app/backups`
- [ ] Off-server copy of `storage/app/backups` + `storage/app/public` scheduled
- [ ] Restore procedure tested (see `docs/BACKUP.md`)

## Verification (post-deploy)
- [ ] `GET /health` → `200 {"status":"ok"}`
- [ ] Super Admin → **System Status** page all green, debug = off
- [ ] Login works for each role; dashboards render
- [ ] Assigning a developer creates an in-app notification (worker processes it)
- [ ] `php artisan test` green in CI before release
