<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @return array<string, list<string>>
     */
    public static function permissionGroups(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view',
            ],
            'Customers' => [
                'customers.view',
                'customers.create',
                'customers.edit',
                'customers.delete',
                'customers.deactivate',
                'customers.documents',
                'customers.ledger',
            ],
            'Chit Schemes' => [
                'schemes.view',
                'schemes.create',
                'schemes.edit',
                'schemes.delete',
                'schemes.status',
            ],
            'Chit Enrollments' => [
                'enrollments.view',
                'enrollments.create',
                'enrollments.edit',
                'enrollments.delete',
                'enrollments.close',
                'enrollments.cancel',
            ],
            'Installments' => [
                'installments.view',
                'installments.generate',
                'installments.edit',
                'installments.status',
            ],
            'Payments' => [
                'payments.view',
                'payments.create',
                'payments.edit',
                'payments.cancel',
                'payments.approve_edit',
            ],
            'Receipts' => [
                'receipts.view',
                'receipts.print',
                'receipts.pdf',
                'receipts.duplicate',
                'receipts.cancel',
                'receipts.whatsapp',
            ],
            'Ledger' => [
                'ledger.view',
                'ledger.customer',
                'ledger.chit',
            ],
            'Pending Dues' => [
                'pending_dues.view',
                'pending_dues.followup',
                'pending_dues.reminder',
            ],
            'Maturity Closing' => [
                'maturity.view',
                'maturity.create',
                'maturity.approve',
                'maturity.cancel',
            ],
            'Jewellery Billing' => [
                'jewellery.view',
                'jewellery.create',
                'jewellery.edit',
                'jewellery.cancel',
                'jewellery.adjust_chit',
            ],
            'Gold Rates' => [
                'gold_rates.view',
                'gold_rates.create',
                'gold_rates.edit',
                'gold_rates.approve',
                'gold_rates.lock',
            ],
            'Staff & Branch' => [
                'staff.view',
                'staff.create',
                'staff.edit',
                'staff.delete',
                'branch.view',
                'branch.create',
                'branch.edit',
                'branch.delete',
                'staff_cash_handover.view',
                'staff_cash_handover.create',
                'staff_cash_handover.receive',
            ],
            'Cashflow' => [
                'cashflow.view',
                'cashflow.create',
                'cashbook.view',
            ],
            'Reports' => [
                'reports.view',
                'reports.export_excel',
                'reports.export_pdf',
                'reports.print',
            ],
            'WhatsApp/SMS' => [
                'messages.view',
                'messages.send',
                'messages.retry',
                'messages.logs',
            ],
            'Admin Settings' => [
                'settings.view',
                'settings.edit',
                'settings.backup',
            ],
            'Audit Logs' => [
                'audit_logs.view',
                'activity_logs.view',
            ],
            'Backup' => [
                'backup.view',
                'backup.create',
                'backup.download',
                'backup.delete',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return collect(static::permissionGroups())->flatten()->values()->all();
    }

    /**
     * @return list<string>
     */
    public static function roleNames(): array
    {
        return ['Admin', 'Manager', 'Staff'];
    }

    /**
     * @return list<string>
     */
    public static function managerDeniedPermissions(): array
    {
        return [
            'settings.backup',
            'backup.delete',
            'staff.delete',
        ];
    }

    /**
     * @return list<string>
     */
    public static function staffPermissionNames(): array
    {
        return [
            'dashboard.view',
            'customers.view',
            'customers.create',
            'customers.edit',
            'enrollments.view',
            'enrollments.create',
            'installments.view',
            'payments.view',
            'payments.create',
            'receipts.view',
            'receipts.print',
            'receipts.pdf',
            'receipts.whatsapp',
            'ledger.view',
            'ledger.customer',
            'ledger.chit',
            'pending_dues.view',
            'pending_dues.followup',
            'pending_dues.reminder',
            'messages.send',
        ];
    }

    /**
     * @return list<string>
     */
    public static function legacyPermissionNames(): array
    {
        return [
            'billing.create',
            'billing.edit',
            'billing.view',
            'branches.manage',
            'branches.view',
            'cashflow.export',
            'communications.send',
            'communications.view',
            'dues.followup',
            'dues.view',
            'gold-rates.manage',
            'gold-rates.view',
            'installments.create',
            'ledger.export',
            'maturity.close',
            'receipts.download',
            'reports.export',
            'staff.manage',
        ];
    }

    /**
     * Seed all role and permission records safely.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = static::permissionNames();
        $legacyPermissions = static::legacyPermissionNames();

        Permission::whereIn('name', array_diff($legacyPermissions, $permissions))->delete();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);

        $adminRole->syncPermissions($permissions);

        $managerRole->syncPermissions(collect($permissions)
            ->reject(fn (string $permission): bool => in_array($permission, static::managerDeniedPermissions(), true))
            ->values()
            ->all());

        $staffRole->syncPermissions(static::staffPermissionNames());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
