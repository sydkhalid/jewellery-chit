# Deployment

## Environment

Set production values on the server:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
QUEUE_CONNECTION=database
BACKUP_DISK=local
```

## Release Commands

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Scheduler

Configure the server cron:

```cron
* * * * * cd /path/to/jewellery-chit && php artisan schedule:run >> /dev/null 2>&1
```

Schedule should cover:

- database backup
- due reminders
- overdue installment status updates

## Permissions

Ensure these paths are writable by the web server:

- `storage`
- `bootstrap/cache`
- public storage symlink target

## Verification

After deployment, verify:

- `/login` loads
- Admin login works
- `/dashboard` loads
- `POST /api/login` returns a Sanctum token
- `GET /api/profile` works with the token
- `php artisan backup:run --only-db --disable-notifications` completes
