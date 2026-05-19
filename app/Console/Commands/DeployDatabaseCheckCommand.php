<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeployDatabaseCheckCommand extends Command
{
    protected $signature = 'deploy:database-check
        {--wait=60 : Maximum seconds to wait for the database}
        {--sleep=3 : Seconds to sleep between connection attempts}';

    protected $description = 'Validate the deploy database configuration before running migrations.';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}", []);
        $host = (string) ($config['host'] ?? '');
        $port = (string) ($config['port'] ?? '');
        $database = (string) ($config['database'] ?? '');
        $urlConfigured = filled($config['url'] ?? null);

        $this->line(sprintf(
            'Database target: env=%s connection=%s host=%s port=%s database=%s url=%s',
            app()->environment(),
            $connection,
            $host !== '' ? $host : '(none)',
            $port !== '' ? $port : '(none)',
            $database !== '' ? $database : '(none)',
            $urlConfigured ? 'set' : 'unset',
        ));

        if (app()->environment('testing') || ($connection === 'sqlite' && $database === ':memory:')) {
            $this->error('Test environment variables are active during deployment.');
            $this->line('Remove .env.testing values from the Railway app service variables.');
            $this->line('Set production variables instead:');
            $this->line('APP_ENV=production');
            $this->line('APP_DEBUG=false');
            $this->line('DB_CONNECTION=mysql');
            $this->line('DB_HOST=${{MySQL.MYSQLHOST}}');
            $this->line('DB_PORT=${{MySQL.MYSQLPORT}}');
            $this->line('DB_DATABASE=${{MySQL.MYSQLDATABASE}}');
            $this->line('DB_USERNAME=${{MySQL.MYSQLUSER}}');
            $this->line('DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}');

            return self::FAILURE;
        }

        if ($connection === 'mysql' && ! $urlConfigured && $host === '127.0.0.1' && $database === 'laravel') {
            $this->error('Railway MySQL variables are not linked to this app service.');
            $this->line('Add Railway service variables such as:');
            $this->line('DB_CONNECTION=mysql');
            $this->line('DB_HOST=${{MySQL.MYSQLHOST}}');
            $this->line('DB_PORT=${{MySQL.MYSQLPORT}}');
            $this->line('DB_DATABASE=${{MySQL.MYSQLDATABASE}}');
            $this->line('DB_USERNAME=${{MySQL.MYSQLUSER}}');
            $this->line('DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}');

            return self::FAILURE;
        }

        $deadline = time() + max(0, (int) $this->option('wait'));
        $sleepSeconds = max(1, (int) $this->option('sleep'));
        $attempt = 0;
        $lastError = null;

        do {
            $attempt++;

            try {
                DB::connection($connection)->getPdo();
                DB::connection($connection)->selectOne('select 1');
                $this->info("Database connection is ready after {$attempt} attempt(s).");

                return self::SUCCESS;
            } catch (Throwable $exception) {
                $lastError = $exception->getMessage();
                $this->warn("Database connection attempt {$attempt} failed: {$lastError}");

                if (time() >= $deadline) {
                    break;
                }

                sleep($sleepSeconds);
            }
        } while (true);

        $this->error('Database connection did not become ready.');

        if ($lastError) {
            $this->line($lastError);
        }

        return self::FAILURE;
    }
}
