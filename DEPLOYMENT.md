# Deployment Guide

Use this checklist for production deployment.

## Server Requirements

- PHP 8.2+
- Composer installed or deploy vendor artifacts from CI
- Node.js/npm if building assets on server
- MySQL or MariaDB
- Web server pointing to `public/`
- Process manager for queue workers
- Cron for scheduler
- HTTPS certificate

## Environment

Set production values in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=warning
QUEUE_CONNECTION=database
CACHE_STORE=redis
SESSION_DRIVER=database
FILESYSTEM_DISK=public
INTEGRATION_MODE=live
```

Never commit `.env`.

## Deploy Commands

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If seeders are required on first deployment:

```bash
php artisan db:seed --force
```

## File Permissions

Ensure these paths are writable by the web server user:

```text
storage/
bootstrap/cache/
```

## Queue Worker

Example Supervisor program:

```ini
[program:jewellery-chit-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/jewellery-chit/artisan queue:work --tries=3 --timeout=120
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/jewellery-chit/storage/logs/worker.log
```

Restart after deployment:

```bash
php artisan queue:restart
```

## Scheduler

Add one cron entry:

```cron
* * * * * cd /path/to/jewellery-chit && php artisan schedule:run >> /dev/null 2>&1
```

Use the scheduler for backups, reminders, overdue installment updates, and queued maintenance commands.

## Integrations

Before enabling live mode:

1. Complete sandbox tests.
2. Configure provider credentials.
3. Register webhook URLs from `API_DOCUMENTATION.md`.
4. Confirm webhooks can reach the server over HTTPS.
5. Monitor `integration_transactions`.

## Backup Before Deploy

```bash
php artisan backup:run --only-db
```

Verify the archive appears in the configured backup disk before migrating.

## Post Deploy Verification

```bash
php artisan route:list
php artisan queue:failed
php artisan test --testsuite=Feature
```

Manual checks:

- Web login works.
- Dashboard loads.
- API login returns a Sanctum token.
- Payment collection creates receipt, ledger, and cashbook entry.
- Backup page lists latest backup.
- Scheduler and queue workers are running.
