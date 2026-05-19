# Backup Guide

The project uses Spatie Laravel Backup and a `backup_logs` table for application-level tracking.

## Configuration

Important `.env` values:

```env
BACKUP_NAME="jewellery-chit"
BACKUP_DISK=local
```

The backup package reads `config/backup.php`. Database backups use the configured Laravel database connection.

## Create Backup

Database only:

```bash
php artisan backup:run --only-db
```

Full backup:

```bash
php artisan backup:run
```

Web panel:

```text
Admin Settings > Backups
```

## Scheduler

Add cron:

```cron
* * * * * cd /path/to/jewellery-chit && php artisan schedule:run >> /dev/null 2>&1
```

Schedule backup commands in `routes/console.php` or the scheduler configuration used by the project.

Recommended policy:

- Daily database backup.
- Weekly full backup.
- Keep local copies short-term.
- Copy critical backups to remote storage.

## Restore Database

1. Put the site in maintenance mode:

```bash
php artisan down
```

2. Download the backup archive.
3. Extract the SQL dump.
4. Restore into a clean database:

```bash
mysql -u DB_USERNAME -p DB_DATABASE < database.sql
```

5. Run migrations if deploying newer code:

```bash
php artisan migrate --force
```

6. Clear caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

7. Bring the app back:

```bash
php artisan up
```

## Restore Files

If public uploads and receipt PDFs are included:

1. Extract `storage/app/public`.
2. Copy files to the server `storage/app/public`.
3. Run:

```bash
php artisan storage:link
```

## Verify Restore

Check:

- Login works.
- Customers and enrollments exist.
- Receipt PDFs open.
- Latest payments, ledgers, and cashbook entries match expected totals.
- Backups page loads.

## Troubleshooting

- Backup fails with database dump error: verify MySQL dump binary is available in system path.
- Archive too large: exclude `vendor`, `node_modules`, logs, and old backups.
- Permission denied: make backup disk writable.
- Download fails: check disk path and web server permissions.
- Restore mismatch: confirm `.env` points to the restored database.
