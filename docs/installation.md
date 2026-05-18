# Installation

## Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- MySQL or compatible database
- PHP extensions required by Laravel, DomPDF, Excel, and Zip backup creation

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jewellery_chit
DB_USERNAME=root
DB_PASSWORD=
```

Run the database setup:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

## Demo Data

Use this command when you need sample records for every module:

```bash
php artisan db:seed --class=DemoDataSeeder
```

The demo seeder is rerunnable and uses stable demo codes such as `CUS-DEMO-001`, `SCH-DEMO-FIXED`, `CHIT-DEMO-001`, `PAY-DEMO-001`, and `INV-DEMO-001`.

## Backup Settings

The backup package reads these optional environment values:

```env
BACKUP_NAME=jewellery-chit
BACKUP_DISK=local
BACKUP_FILENAME_PREFIX=jewellery-chit-
```

Backups can also be controlled from Admin Settings and the Backups page.
