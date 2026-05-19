<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\GoldRate;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\ChitEnrollmentService;
use App\Services\InstallmentService;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Throwable;

class ImportLiveDataCommand extends Command
{
    protected $signature = 'live-data:import
        {--customers= : CSV file containing real customer records}
        {--chits= : CSV file containing real chit account records}
        {--staff= : CSV file containing real staff users}
        {--gold-rates= : CSV file containing approved gold/silver rates}
        {--settings= : CSV file containing shop, receipt, backup, and reminder settings}
        {--actor=admin@example.com : Existing admin email used for audit fields}
        {--dry-run : Validate and summarize without committing}
        {--skip-schedules : Create chit accounts without generating installment schedules}';

    protected $description = 'Import live customers, chit accounts, staff users, gold rates, and production settings from CSV files.';

    /**
     * @var array<string, int>
     */
    private array $summary = [
        'settings_created' => 0,
        'settings_updated' => 0,
        'staff_created' => 0,
        'staff_updated' => 0,
        'gold_rates_created' => 0,
        'gold_rates_updated' => 0,
        'customers_created' => 0,
        'customers_updated' => 0,
        'chits_created' => 0,
        'chits_updated' => 0,
        'schedules_generated' => 0,
        'opening_balances_created' => 0,
    ];

    public function handle(InstallmentService $installments, LedgerService $ledgers, ChitEnrollmentService $enrollments): int
    {
        if (! $this->hasImportFile()) {
            $this->error('Provide at least one import file option. See docs/import-templates.');

            return self::FAILURE;
        }

        $actor = User::query()->where('email', (string) $this->option('actor'))->first();

        if (! $actor) {
            $this->error('Import actor was not found: '.$this->option('actor'));

            return self::FAILURE;
        }

        Auth::login($actor);

        DB::beginTransaction();

        try {
            if ($this->option('settings')) {
                $this->importSettings((string) $this->option('settings'));
            }

            if ($this->option('staff')) {
                $this->importStaff((string) $this->option('staff'));
            }

            if ($this->option('gold-rates')) {
                $this->importGoldRates((string) $this->option('gold-rates'), $actor);
            }

            if ($this->option('customers')) {
                $this->importCustomers((string) $this->option('customers'), $actor);
            }

            if ($this->option('chits')) {
                $this->importChits((string) $this->option('chits'), $actor, $installments, $ledgers, $enrollments);
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('Dry run completed. No database changes were saved.');
            } else {
                ShopSetting::updateByKey('go_live_import_completed_at', now()->toDateTimeString(), 'text', 'go_live');
                ShopSetting::updateByKey('go_live_last_import_summary', $this->summary, 'json', 'go_live');
                DB::commit();
                $this->info('Live data import completed.');
            }

            $this->renderSummary();

            return self::SUCCESS;
        } catch (Throwable $exception) {
            DB::rollBack();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function hasImportFile(): bool
    {
        return collect(['customers', 'chits', 'staff', 'gold-rates', 'settings'])
            ->contains(fn (string $option): bool => filled($this->option($option)));
    }

    private function importSettings(string $path): void
    {
        foreach ($this->readCsv($path) as $line => $row) {
            $key = $this->required($row, 'key', $line);
            $exists = ShopSetting::query()->where('key', $key)->exists();

            ShopSetting::updateByKey(
                $key,
                $row['value'] ?? null,
                $row['type'] ?? 'text',
                $row['group_name'] ?? null
            );

            $this->summary[$exists ? 'settings_updated' : 'settings_created']++;
        }
    }

    private function importStaff(string $path): void
    {
        foreach ($this->readCsv($path) as $line => $row) {
            $email = strtolower($this->required($row, 'email', $line));
            $roleName = $row['role'] ?? 'Staff';
            $branch = $this->branchByCode($row['branch_code'] ?? 'MAIN', $line);

            if (! Role::query()->where('name', $roleName)->exists()) {
                throw new \RuntimeException("Line {$line}: role does not exist: {$roleName}");
            }

            $user = User::query()->where('email', $email)->first();
            $created = ! $user;
            $payload = [
                'name' => $this->required($row, 'name', $line),
                'mobile' => $row['mobile'] ?? null,
                'branch_id' => $branch->id,
                'status' => $row['status'] ?? 'active',
                'email_verified_at' => now(),
            ];

            if (filled($row['password'] ?? null)) {
                $payload['password'] = Hash::make((string) $row['password']);
            } elseif ($created) {
                throw new \RuntimeException("Line {$line}: password is required for new staff user {$email}");
            }

            $user = User::query()->updateOrCreate(['email' => $email], $payload);
            $user->syncRoles([$roleName]);

            $this->summary[$created ? 'staff_created' : 'staff_updated']++;
        }
    }

    private function importGoldRates(string $path, User $actor): void
    {
        foreach ($this->readCsv($path) as $line => $row) {
            $date = Carbon::parse($this->required($row, 'rate_date', $line))->toDateString();
            $status = $row['status'] ?? 'approved';
            $exists = GoldRate::query()->whereDate('rate_date', $date)->exists();

            GoldRate::query()->updateOrCreate(
                ['rate_date' => $date],
                [
                    'gold_22k' => $this->decimal($row['gold_22k'] ?? 0),
                    'gold_24k' => $this->decimal($row['gold_24k'] ?? 0),
                    'silver_rate' => $this->decimal($row['silver_rate'] ?? 0),
                    'status' => $status,
                    'rate_locked' => $this->boolean($row['rate_locked'] ?? true),
                    'approved_by' => $status === 'approved' ? $actor->id : null,
                    'approved_at' => $status === 'approved' ? now() : null,
                    'created_by' => $actor->id,
                ]
            );

            $this->summary[$exists ? 'gold_rates_updated' : 'gold_rates_created']++;
        }
    }

    private function importCustomers(string $path, User $actor): void
    {
        foreach ($this->readCsv($path) as $line => $row) {
            $mobile = $this->required($row, 'mobile', $line);
            $customerCode = $row['customer_code'] ?? null;
            $customer = $this->findCustomerForImport($customerCode, $mobile, $line);
            $created = ! $customer;

            $customer ??= new Customer([
                'customer_code' => filled($customerCode)
                    ? $customerCode
                    : app(\App\Services\CustomerService::class)->generateCustomerCode(),
            ]);

            if (method_exists($customer, 'trashed') && $customer->trashed()) {
                $customer->restore();
            }

            $customer->fill([
                'name' => $this->required($row, 'name', $line),
                'mobile' => $mobile,
                'alternate_mobile' => $row['alternate_mobile'] ?? null,
                'email' => $row['email'] ?? null,
                'aadhaar_no' => $row['aadhaar_no'] ?? null,
                'pan_no' => $row['pan_no'] ?? null,
                'address' => $row['address'] ?? '',
                'city' => $row['city'] ?? '',
                'state' => $row['state'] ?? '',
                'pincode' => $row['pincode'] ?? '',
                'status' => $row['status'] ?? 'active',
                'created_by' => $customer->exists ? $customer->created_by : $actor->id,
                'updated_by' => $actor->id,
            ]);
            $customer->save();

            if (filled($row['nominee_name'] ?? null)) {
                $customer->nominee()->updateOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'name' => $row['nominee_name'],
                        'relationship' => $row['nominee_relationship'] ?? '',
                        'mobile' => $row['nominee_mobile'] ?? null,
                        'address' => $row['nominee_address'] ?? null,
                        'aadhaar_no' => $row['nominee_aadhaar_no'] ?? null,
                    ]
                );
            }

            $this->summary[$created ? 'customers_created' : 'customers_updated']++;
        }
    }

    private function importChits(
        string $path,
        User $actor,
        InstallmentService $installments,
        LedgerService $ledgers,
        ChitEnrollmentService $enrollments
    ): void {
        foreach ($this->readCsv($path) as $line => $row) {
            $customer = $this->customerByImportKeys($row, $line);
            $scheme = $this->schemeByCode($this->required($row, 'scheme_code', $line), $line);
            $branch = $this->branchByCode($row['branch_code'] ?? 'MAIN', $line);
            $staff = filled($row['assigned_staff_email'] ?? null)
                ? User::query()->where('email', strtolower((string) $row['assigned_staff_email']))->first()
                : null;

            if (filled($row['assigned_staff_email'] ?? null) && ! $staff) {
                throw new \RuntimeException("Line {$line}: assigned staff was not found.");
            }

            $chitNo = $row['chit_no'] ?? $enrollments->generateChitNumber();
            $enrollment = ChitEnrollment::withTrashed()->where('chit_no', $chitNo)->first();
            $created = ! $enrollment;
            $enrollment ??= new ChitEnrollment(['chit_no' => $chitNo]);

            if (method_exists($enrollment, 'trashed') && $enrollment->trashed()) {
                $enrollment->restore();
            }

            $startDate = Carbon::parse($this->required($row, 'start_date', $line));
            $totalMonths = (int) ($row['total_months'] ?? $scheme->duration_months);
            $monthlyAmount = $this->resolveImportedMonthlyAmount($row, $scheme);
            $totalPayable = $this->decimal($row['total_payable'] ?? ($monthlyAmount * $totalMonths));
            $totalPaid = $this->decimal($row['total_paid'] ?? 0);

            $enrollment->fill([
                'customer_id' => $customer->id,
                'scheme_id' => $scheme->id,
                'branch_id' => $branch->id,
                'assigned_staff_id' => $staff?->id,
                'start_date' => $startDate->toDateString(),
                'monthly_due_date' => (int) ($row['monthly_due_date'] ?? $startDate->format('d')),
                'maturity_date' => filled($row['maturity_date'] ?? null)
                    ? Carbon::parse((string) $row['maturity_date'])->toDateString()
                    : $startDate->copy()->addMonthsNoOverflow($totalMonths)->toDateString(),
                'remarks' => $row['remarks'] ?? null,
                'total_months' => $totalMonths,
                'monthly_amount' => $scheme->scheme_type === 'gold_weight' ? null : $monthlyAmount,
                'total_payable' => $totalPayable,
                'total_paid' => $totalPaid,
                'total_pending' => max(0, round($totalPayable - $totalPaid, 2)),
                'status' => $row['status'] ?? 'active',
                'created_by' => $enrollment->exists ? $enrollment->created_by : $actor->id,
                'updated_by' => $actor->id,
            ]);
            $enrollment->save();

            if (! $this->option('skip-schedules') && ! $enrollment->installments()->exists()) {
                $installments->generateSchedule($enrollment);
                $this->summary['schedules_generated']++;
            }

            if ((float) $totalPaid > 0 && ! $enrollment->payments()->exists()) {
                $this->applyOpeningPaidAmount($enrollment, $totalPaid, $row['last_paid_date'] ?? null, $ledgers, $actor);
            }

            $this->summary[$created ? 'chits_created' : 'chits_updated']++;
        }
    }

    /**
     * @return iterable<int, array<string, string|null>>
     */
    private function readCsv(string $path): iterable
    {
        $resolvedPath = $this->resolvePath($path);
        $handle = fopen($resolvedPath, 'rb');

        if (! $handle) {
            throw new \RuntimeException('Unable to open CSV file: '.$resolvedPath);
        }

        $headers = null;
        $line = 0;

        try {
            while (($values = fgetcsv($handle)) !== false) {
                $line++;

                if ($values === [null] || $values === false) {
                    continue;
                }

                if ($headers === null) {
                    $headers = array_map(fn (?string $header): string => $this->normalizeHeader($header ?? ''), $values);
                    continue;
                }

                if (collect($values)->filter(fn ($value): bool => filled($value))->isEmpty()) {
                    continue;
                }

                $values = array_slice(array_pad($values, count($headers), null), 0, count($headers));

                yield $line => array_combine($headers, $values);
            }
        } finally {
            fclose($handle);
        }
    }

    private function resolvePath(string $path): string
    {
        $candidates = [
            $path,
            base_path($path),
            storage_path($path),
            storage_path('app/'.$path),
        ];

        foreach ($candidates as $candidate) {
            if (File::isFile($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('CSV file not found: '.$path);
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', trim($header)));
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function required(array $row, string $key, int $line): string
    {
        $value = $row[$key] ?? null;

        if (! filled($value)) {
            throw new \RuntimeException("Line {$line}: {$key} is required.");
        }

        return trim((string) $value);
    }

    private function decimal(mixed $value): float
    {
        if (! filled($value)) {
            return 0.0;
        }

        return round((float) str_replace([',', 'Rs.', 'rs.'], '', (string) $value), 2);
    }

    private function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function branchByCode(?string $branchCode, int $line): Branch
    {
        $branch = Branch::query()->where('branch_code', $branchCode ?: 'MAIN')->first();

        if (! $branch) {
            throw new \RuntimeException("Line {$line}: branch was not found: ".($branchCode ?: 'MAIN'));
        }

        return $branch;
    }

    private function schemeByCode(string $schemeCode, int $line): ChitScheme
    {
        $scheme = ChitScheme::query()->where('scheme_code', $schemeCode)->first();

        if (! $scheme) {
            throw new \RuntimeException("Line {$line}: scheme was not found: {$schemeCode}");
        }

        return $scheme;
    }

    private function findCustomerForImport(?string $customerCode, string $mobile, int $line): ?Customer
    {
        $byCode = filled($customerCode)
            ? Customer::withTrashed()->where('customer_code', $customerCode)->first()
            : null;
        $byMobile = Customer::withTrashed()->where('mobile', $mobile)->first();

        if ($byCode && $byMobile && $byCode->id !== $byMobile->id) {
            throw new \RuntimeException("Line {$line}: customer_code and mobile belong to different customers.");
        }

        return $byCode ?: $byMobile;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function customerByImportKeys(array $row, int $line): Customer
    {
        $query = Customer::query();

        if (filled($row['customer_code'] ?? null)) {
            $query->where('customer_code', $row['customer_code']);
        } elseif (filled($row['customer_mobile'] ?? null)) {
            $query->where('mobile', $row['customer_mobile']);
        } else {
            throw new \RuntimeException("Line {$line}: customer_code or customer_mobile is required.");
        }

        $customer = $query->first();

        if (! $customer) {
            throw new \RuntimeException("Line {$line}: customer was not found for chit import.");
        }

        return $customer;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function resolveImportedMonthlyAmount(array $row, ChitScheme $scheme): float
    {
        if (filled($row['monthly_amount'] ?? null)) {
            return $this->decimal($row['monthly_amount']);
        }

        return match ($scheme->scheme_type) {
            'fixed_amount' => (float) $scheme->monthly_amount,
            'flexible_amount' => (float) $scheme->min_amount,
            default => 0.0,
        };
    }

    private function applyOpeningPaidAmount(
        ChitEnrollment $enrollment,
        float $totalPaid,
        mixed $lastPaidDate,
        LedgerService $ledgers,
        User $actor
    ): void {
        $remaining = $totalPaid;
        $paidDate = filled($lastPaidDate) ? Carbon::parse((string) $lastPaidDate)->toDateString() : now()->toDateString();

        ChitInstallment::query()
            ->where('enrollment_id', $enrollment->id)
            ->orderBy('installment_no')
            ->get()
            ->each(function (ChitInstallment $installment) use (&$remaining, $paidDate): void {
                if ($remaining <= 0) {
                    return;
                }

                $dueTotal = (float) $installment->due_amount + (float) $installment->late_fee;
                $applied = min($remaining, $dueTotal);
                $balance = max(0, round($dueTotal - $applied, 2));

                $installment->update([
                    'paid_amount' => $applied,
                    'balance_amount' => $balance,
                    'status' => $balance <= 0 ? 'paid' : 'partial',
                    'paid_date' => $applied > 0 ? $paidDate : null,
                ]);

                $remaining = round($remaining - $applied, 2);
            });

        $ledgers->createLedgerEntry([
            'enrollment_id' => $enrollment->id,
            'customer_id' => $enrollment->customer_id,
            'transaction_date' => $paidDate,
            'transaction_type' => 'opening_balance',
            'debit' => 0,
            'credit' => $totalPaid,
            'reference_type' => ChitEnrollment::class,
            'reference_id' => $enrollment->id,
            'remarks' => 'Opening paid balance imported during go-live.',
            'created_by' => $actor->id,
            'prevent_duplicate' => true,
        ]);

        $ledgers->calculateRunningBalance($enrollment);
        $this->summary['opening_balances_created']++;
    }

    private function renderSummary(): void
    {
        $this->table(
            ['Item', 'Count'],
            collect($this->summary)
                ->map(fn (int $count, string $key): array => [str_replace('_', ' ', $key), $count])
                ->values()
                ->all()
        );
    }
}
