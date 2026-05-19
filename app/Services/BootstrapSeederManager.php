<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PaymentMode;
use App\Models\ShopSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BootstrapSeederManager
{
    /**
     * @return array<string, array{label: string, description: string, icon: string}>
     */
    public function definitions(): array
    {
        return [
            'roles_permissions' => [
                'label' => 'Roles & Permissions',
                'description' => 'Admin, Manager, Staff roles and panel permissions.',
                'icon' => 'bi-shield-lock',
            ],
            'main_branch' => [
                'label' => 'Main Branch',
                'description' => 'Default MAIN branch record.',
                'icon' => 'bi-building',
            ],
            'payment_modes' => [
                'label' => 'Payment Modes',
                'description' => 'Cash, UPI, Card, Bank Transfer, and Cheque.',
                'icon' => 'bi-credit-card',
            ],
            'shop_settings' => [
                'label' => 'Shop Settings',
                'description' => 'Default shop, receipt, chit, message, and backup settings.',
                'icon' => 'bi-sliders',
            ],
            'default_users' => [
                'label' => 'Default Users',
                'description' => 'Admin, Manager, and Staff login users.',
                'icon' => 'bi-person-check',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * @return array<string, array{label: string, description: string, icon: string, done: bool, summary: string}>
     */
    public function statuses(): array
    {
        return collect($this->definitions())
            ->map(fn (array $definition, string $key): array => [
                ...$definition,
                ...$this->statusFor($key),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $keys
     * @return array{ran: list<string>, skipped: list<string>, invalid: list<string>}
     */
    public function runSelected(array $keys): array
    {
        $definitions = $this->definitions();
        $result = [
            'ran' => [],
            'skipped' => [],
            'invalid' => [],
        ];

        foreach (array_values(array_unique($keys)) as $key) {
            if (! isset($definitions[$key])) {
                $result['invalid'][] = $key;

                continue;
            }

            if ($this->statusFor($key)['done']) {
                $result['skipped'][] = $definitions[$key]['label'];

                continue;
            }

            $this->runSeeder($key);

            if ($this->statusFor($key)['done']) {
                $result['ran'][] = $definitions[$key]['label'];
            } else {
                $result['skipped'][] = $definitions[$key]['label'];
            }
        }

        return $result;
    }

    /**
     * @return array{ran: list<string>, skipped: list<string>, invalid: list<string>}
     */
    public function runAllPending(): array
    {
        return $this->runSelected($this->keys());
    }

    /**
     * @return array{done: bool, summary: string}
     */
    private function statusFor(string $key): array
    {
        return match ($key) {
            'roles_permissions' => $this->rolesPermissionsStatus(),
            'main_branch' => $this->mainBranchStatus(),
            'payment_modes' => $this->paymentModesStatus(),
            'shop_settings' => $this->shopSettingsStatus(),
            'default_users' => $this->defaultUsersStatus(),
            default => ['done' => false, 'summary' => 'Unknown seeder'],
        };
    }

    /**
     * @return array{done: bool, summary: string}
     */
    private function rolesPermissionsStatus(): array
    {
        $permissions = RolePermissionSeeder::permissionNames();
        $roles = RolePermissionSeeder::roleNames();
        $existingPermissions = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->all();
        $existingRoles = Role::query()
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();
        $legacyCount = Permission::query()
            ->whereIn('name', RolePermissionSeeder::legacyPermissionNames())
            ->count();
        $missingPermissions = array_diff($permissions, $existingPermissions);
        $missingRoles = array_diff($roles, $existingRoles);
        $assignmentsReady = empty($missingPermissions)
            && empty($missingRoles)
            && $legacyCount === 0
            && $this->roleAssignmentsReady($permissions);
        $done = empty($missingPermissions) && empty($missingRoles) && $legacyCount === 0 && $assignmentsReady;

        if ($done) {
            return ['done' => true, 'summary' => count($permissions).' permissions and '.count($roles).' roles ready'];
        }

        if (! empty($missingPermissions) || ! empty($missingRoles) || $legacyCount > 0) {
            return [
                'done' => false,
                'summary' => count($missingPermissions).' permissions missing, '.count($missingRoles).' roles missing, '.$legacyCount.' legacy permissions found',
            ];
        }

        return ['done' => false, 'summary' => 'Role permission assignments need sync'];
    }

    /**
     * @param  list<string>  $permissions
     */
    private function roleAssignmentsReady(array $permissions): bool
    {
        $adminRole = Role::query()->where('name', 'Admin')->where('guard_name', 'web')->first();
        $managerRole = Role::query()->where('name', 'Manager')->where('guard_name', 'web')->first();
        $staffRole = Role::query()->where('name', 'Staff')->where('guard_name', 'web')->first();

        if (! $adminRole || ! $managerRole || ! $staffRole) {
            return false;
        }

        $managerPermissions = collect($permissions)
            ->reject(fn (string $permission): bool => in_array($permission, RolePermissionSeeder::managerDeniedPermissions(), true))
            ->values()
            ->all();

        return $adminRole->hasAllPermissions($permissions)
            && $managerRole->hasAllPermissions($managerPermissions)
            && $staffRole->hasAllPermissions(RolePermissionSeeder::staffPermissionNames());
    }

    /**
     * @return array{done: bool, summary: string}
     */
    private function mainBranchStatus(): array
    {
        $done = Branch::query()->where('branch_code', 'MAIN')->exists();

        return [
            'done' => $done,
            'summary' => $done ? 'MAIN branch exists' : 'MAIN branch missing',
        ];
    }

    /**
     * @return array{done: bool, summary: string}
     */
    private function paymentModesStatus(): array
    {
        $codes = array_column($this->defaultPaymentModes(), 'code');
        $existing = PaymentMode::query()->whereIn('code', $codes)->pluck('code')->all();
        $missing = array_diff($codes, $existing);

        return [
            'done' => empty($missing),
            'summary' => empty($missing) ? count($codes).' payment modes ready' : count($missing).' payment modes missing',
        ];
    }

    /**
     * @return array{done: bool, summary: string}
     */
    private function shopSettingsStatus(): array
    {
        $keys = array_column($this->defaultShopSettings(), 'key');
        $existing = ShopSetting::query()->whereIn('key', $keys)->pluck('key')->all();
        $missing = array_diff($keys, $existing);

        return [
            'done' => empty($missing),
            'summary' => empty($missing) ? count($keys).' settings ready' : count($missing).' settings missing',
        ];
    }

    /**
     * @return array{done: bool, summary: string}
     */
    private function defaultUsersStatus(): array
    {
        $missing = collect($this->defaultUsers())
            ->filter(function (array $userData): bool {
                $user = User::query()->where('email', $userData['email'])->first();

                return ! $user || ! $user->hasRole($userData['role']);
            })
            ->count();

        return [
            'done' => $missing === 0,
            'summary' => $missing === 0 ? count($this->defaultUsers()).' users ready' : $missing.' users missing roles or records',
        ];
    }

    private function runSeeder(string $key): void
    {
        match ($key) {
            'roles_permissions' => $this->runRolePermissions(),
            'main_branch' => $this->runMainBranch(),
            'payment_modes' => $this->runPaymentModes(),
            'shop_settings' => $this->runShopSettings(),
            'default_users' => $this->runDefaultUsers(),
            default => null,
        };
    }

    private function runRolePermissions(): void
    {
        app(RolePermissionSeeder::class)->run();
    }

    private function runMainBranch(): Branch
    {
        $branch = Branch::withTrashed()->firstOrNew(['branch_code' => 'MAIN']);

        if (! $branch->exists) {
            $branch->fill([
                'name' => 'Main Branch',
                'mobile' => null,
                'email' => null,
                'address' => '',
                'city' => '',
                'state' => '',
                'pincode' => '',
                'status' => 'active',
            ]);
        }

        if ($branch->trashed()) {
            $branch->restore();
        }

        $branch->save();

        return $branch;
    }

    private function runPaymentModes(): void
    {
        foreach ($this->defaultPaymentModes() as $mode) {
            PaymentMode::firstOrCreate(
                ['code' => $mode['code']],
                [
                    'name' => $mode['name'],
                    'status' => 'active',
                ]
            );
        }
    }

    private function runShopSettings(): void
    {
        foreach ($this->defaultShopSettings() as $setting) {
            ShopSetting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group_name' => $setting['group_name'],
                ]
            );
        }
    }

    private function runDefaultUsers(): void
    {
        if (! $this->rolesPermissionsStatus()['done']) {
            $this->runRolePermissions();
        }

        $branch = Branch::query()->where('branch_code', 'MAIN')->first() ?? $this->runMainBranch();

        foreach ($this->defaultUsers() as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'mobile' => $userData['mobile'],
                    'password' => Hash::make('password'),
                    'branch_id' => $branch->id,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }

    /**
     * @return list<array{name: string, code: string}>
     */
    private function defaultPaymentModes(): array
    {
        return [
            ['name' => 'Cash', 'code' => 'cash'],
            ['name' => 'UPI', 'code' => 'upi'],
            ['name' => 'Card', 'code' => 'card'],
            ['name' => 'Bank Transfer', 'code' => 'bank_transfer'],
            ['name' => 'Cheque', 'code' => 'cheque'],
        ];
    }

    /**
     * @return list<array{key: string, value: string|null, type: string, group_name: string}>
     */
    private function defaultShopSettings(): array
    {
        return [
            ['key' => 'shop_name', 'value' => 'Jewellery Chit', 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'shop_logo', 'value' => null, 'type' => 'file', 'group_name' => 'shop'],
            ['key' => 'shop_address', 'value' => null, 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'shop_mobile', 'value' => null, 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'shop_email', 'value' => null, 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'gstin', 'value' => null, 'type' => 'text', 'group_name' => 'tax'],
            ['key' => 'receipt_prefix', 'value' => 'RCPT', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'chit_number_prefix', 'value' => 'CHIT', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'payment_number_prefix', 'value' => 'PAY', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'closure_number_prefix', 'value' => 'CLS', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'refund_number_prefix', 'value' => 'REF', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'invoice_number_prefix', 'value' => 'INV', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'handover_number_prefix', 'value' => 'HND', 'type' => 'text', 'group_name' => 'numbering'],
            ['key' => 'financial_year', 'value' => now()->format('Y').'-'.now()->addYear()->format('y'), 'type' => 'text', 'group_name' => 'shop'],
            ['key' => 'terms_and_conditions', 'value' => null, 'type' => 'text', 'group_name' => 'documents'],
            ['key' => 'whatsapp_enabled', 'value' => '0', 'type' => 'boolean', 'group_name' => 'whatsapp'],
            ['key' => 'whatsapp_api_url', 'value' => null, 'type' => 'text', 'group_name' => 'whatsapp'],
            ['key' => 'whatsapp_api_key', 'value' => null, 'type' => 'text', 'group_name' => 'whatsapp'],
            ['key' => 'sms_enabled', 'value' => '0', 'type' => 'boolean', 'group_name' => 'sms'],
            ['key' => 'sms_api_url', 'value' => null, 'type' => 'text', 'group_name' => 'sms'],
            ['key' => 'sms_api_key', 'value' => null, 'type' => 'text', 'group_name' => 'sms'],
            ['key' => 'backup_enabled', 'value' => '1', 'type' => 'boolean', 'group_name' => 'backup'],
            ['key' => 'backup_disk', 'value' => 'local', 'type' => 'text', 'group_name' => 'backup'],
            ['key' => 'default_grace_period_days', 'value' => '0', 'type' => 'number', 'group_name' => 'chit'],
            ['key' => 'default_late_fee_type', 'value' => 'none', 'type' => 'text', 'group_name' => 'chit'],
            ['key' => 'default_late_fee_value', 'value' => '0', 'type' => 'number', 'group_name' => 'chit'],
        ];
    }

    /**
     * @return list<array{name: string, email: string, mobile: string, role: string}>
     */
    private function defaultUsers(): array
    {
        return [
            ['name' => 'Admin', 'email' => 'admin@example.com', 'mobile' => '9999999999', 'role' => 'Admin'],
            ['name' => 'Manager', 'email' => 'manager@example.com', 'mobile' => '8888888888', 'role' => 'Manager'],
            ['name' => 'Staff', 'email' => 'staff@example.com', 'mobile' => '7777777777', 'role' => 'Staff'],
        ];
    }
}
