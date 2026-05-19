<?php

namespace App\Console\Commands;

use App\Models\ChitEnrollment;
use App\Models\ChitPayment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\GoldRate;
use App\Models\PaymentMode;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class GoLiveVerifyCommand extends Command
{
    protected $signature = 'go-live:verify
        {--json : Output machine-readable JSON}
        {--fail-on-blockers : Return a failing exit code when required checks fail}';

    protected $description = 'Verify live ERP readiness for data, queues, scheduler, backups, reminders, reports, payments, and APIs.';

    public function handle(Schedule $schedule): int
    {
        $checks = collect([
            $this->check('Data', 'Live data import has been confirmed', filled(ShopSetting::getByKey('go_live_import_completed_at')), filled(ShopSetting::getByKey('go_live_import_completed_at')) ? 'Last import: '.ShopSetting::getByKey('go_live_import_completed_at') : 'Run live-data:import with real CSV files', true),
            $this->check('Data', 'Customer records are available', Customer::query()->count() > 0, Customer::query()->count().' customers found', true),
            $this->check('Data', 'Chit accounts are available', ChitEnrollment::query()->count() > 0, ChitEnrollment::query()->count().' chit accounts found', true),
            $this->check('Data', 'Active chit schemes exist', ChitScheme::query()->where('status', 'active')->count() > 0, ChitScheme::query()->where('status', 'active')->count().' active schemes found', true),
            $this->checkGoldRates(),
            $this->checkReceiptSettings(),
            $this->checkStaffUsers(),
            $this->checkBackups(),
            $this->checkReminders(),
            $this->checkQueues(),
            $this->checkScheduler($schedule),
            $this->checkReports(),
            $this->checkPayments(),
            $this->checkApis(),
            $this->check('Collections', 'Live collection can start', Customer::query()->count() > 0 && ChitEnrollment::query()->where('status', 'active')->exists() && PaymentMode::query()->exists(), 'Requires customers, active chits, and payment modes', true),
        ]);

        $blockers = $checks->where('required', true)->where('passed', false)->count();
        $warnings = $checks->where('required', false)->where('passed', false)->count();
        $readiness = $blockers === 0 ? ($warnings === 0 ? 'READY' : 'READY_WITH_WARNINGS') : 'BLOCKED';

        if ($this->option('json')) {
            $this->line(json_encode([
                'readiness' => $readiness,
                'blockers' => $blockers,
                'warnings' => $warnings,
                'checks' => $checks->values()->all(),
            ], JSON_PRETTY_PRINT));
        } else {
            $this->info('Go-live readiness: '.$readiness);
            $this->table(
                ['Area', 'Check', 'Status', 'Required', 'Details'],
                $checks->map(fn (array $check): array => [
                    $check['area'],
                    $check['name'],
                    $check['passed'] ? 'PASS' : 'FAIL',
                    $check['required'] ? 'Yes' : 'No',
                    $check['details'],
                ])->all()
            );
        }

        if ($blockers > 0 && $this->option('fail-on-blockers')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function check(string $area, string $name, bool $passed, string $details, bool $required = false): array
    {
        return compact('area', 'name', 'passed', 'details', 'required');
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkGoldRates(): array
    {
        $latestRate = GoldRate::query()
            ->where('status', 'approved')
            ->latest('rate_date')
            ->first();

        return $this->check(
            'Gold Rates',
            'Approved gold rate is configured',
            (bool) $latestRate,
            $latestRate ? 'Latest approved rate date: '.$latestRate->rate_date?->toDateString() : 'No approved gold rate found',
            true
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkReceiptSettings(): array
    {
        $requiredSettings = ['shop_name', 'receipt_prefix', 'terms_and_conditions'];
        $missing = collect($requiredSettings)
            ->filter(fn (string $key): bool => blank(ShopSetting::getByKey($key)))
            ->values();

        return $this->check(
            'Receipts',
            'Receipt/shop settings are configured',
            $missing->isEmpty(),
            $missing->isEmpty() ? 'Required receipt settings are present' : 'Missing: '.$missing->implode(', '),
            true
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkStaffUsers(): array
    {
        $counts = collect(['Admin', 'Manager', 'Staff'])
            ->mapWithKeys(fn (string $role): array => [$role => User::role($role)->where('status', 'active')->count()]);
        $missing = $counts->filter(fn (int $count): bool => $count === 0)->keys();

        return $this->check(
            'Staff',
            'Active admin, manager, and staff users exist',
            $missing->isEmpty(),
            $missing->isEmpty() ? $counts->map(fn (int $count, string $role): string => "{$role}: {$count}")->implode(', ') : 'Missing active roles: '.$missing->implode(', '),
            true
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkBackups(): array
    {
        $enabled = (bool) ShopSetting::getByKey('backup_enabled', false);
        $hasCommand = array_key_exists('backup:run', $this->getApplication()?->all() ?? []);
        $disk = ShopSetting::getByKey('backup_disk', config('backup.backup.destination.disks.0', 'local'));

        return $this->check(
            'Backups',
            'Backup command and disk are configured',
            $enabled && $hasCommand && filled($disk),
            'enabled='.($enabled ? 'yes' : 'no').', command='.($hasCommand ? 'yes' : 'no').', disk='.(string) $disk,
            true
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkReminders(): array
    {
        $whatsapp = (bool) ShopSetting::getByKey('whatsapp_enabled', false);
        $sms = (bool) ShopSetting::getByKey('sms_enabled', false);
        $routesExist = Route::has('pending-dues.reminder') && Route::has('pending-dues.bulk-reminder');

        return $this->check(
            'Reminders',
            'Reminder channel and routes are enabled',
            $routesExist && ($whatsapp || $sms),
            'routes='.($routesExist ? 'yes' : 'no').', whatsapp='.($whatsapp ? 'enabled' : 'disabled').', sms='.($sms ? 'enabled' : 'disabled'),
            false
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkQueues(): array
    {
        $connection = config('queue.default');
        $databaseQueueReady = $connection !== 'database' || Schema::hasTable(config('queue.connections.database.table', 'jobs'));

        return $this->check(
            'Queues',
            'Queue connection is production-ready',
            $connection !== 'sync' && $databaseQueueReady,
            'connection='.(string) $connection.', jobs_table='.($databaseQueueReady ? 'ready' : 'missing'),
            true
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkScheduler(Schedule $schedule): array
    {
        $events = collect($schedule->events())->map(fn ($event): string => (string) $event->command);
        $requiredCommands = ['installments:mark-overdue', 'reminders:send-due', 'backup:run'];
        $missing = collect($requiredCommands)
            ->reject(fn (string $command): bool => $events->contains(fn (string $event): bool => str_contains($event, $command)))
            ->values();

        return $this->check(
            'Scheduler',
            'Daily scheduler includes overdue, reminder, and backup tasks',
            $missing->isEmpty(),
            $missing->isEmpty() ? 'Scheduled tasks are registered' : 'Missing: '.$missing->implode(', '),
            true
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkReports(): array
    {
        $routes = ['reports.index', 'reports.collections', 'reports.pending', 'reports.receipts', 'reports.cashflow'];
        $missing = collect($routes)->reject(fn (string $route): bool => Route::has($route))->values();

        return $this->check(
            'Reports',
            'Core report routes are available',
            $missing->isEmpty(),
            $missing->isEmpty() ? 'Core report routes available' : 'Missing routes: '.$missing->implode(', '),
            true
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkPayments(): array
    {
        $paymentModes = PaymentMode::query()->where('status', 'active')->count();
        $routesReady = Route::has('payments.index') && Route::has('payments.create') && Route::has('payments.store');

        return $this->check(
            'Payments',
            'Payment collection setup is ready',
            $paymentModes > 0 && $routesReady,
            "{$paymentModes} active payment modes, routes=".($routesReady ? 'ready' : 'missing'),
            true
        );
    }

    /**
     * @return array{area: string, name: string, passed: bool, details: string, required: bool}
     */
    private function checkApis(): array
    {
        $routes = Route::getRoutes();
        $hasLogin = collect($routes)->contains(function ($route): bool {
            return in_array('POST', $route->methods(), true) && $route->uri() === 'api/login';
        });
        $hasProtectedUser = collect($routes)->contains(function ($route): bool {
            return in_array('GET', $route->methods(), true)
                && $route->uri() === 'api/user'
                && collect($route->middleware())->contains('auth:sanctum');
        });

        return $this->check(
            'APIs',
            'Production API auth routes are available',
            $hasLogin && $hasProtectedUser && (int) config('sanctum.expiration') > 0,
            'login='.($hasLogin ? 'yes' : 'no').', sanctum_user='.($hasProtectedUser ? 'yes' : 'no').', token_expiry='.(string) config('sanctum.expiration'),
            true
        );
    }
}
