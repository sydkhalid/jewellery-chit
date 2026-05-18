<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use App\Models\Branch;
use App\Models\PaymentMode;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_permissions_and_default_admin_are_seeded(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Role::where('name', 'Admin')->exists());
        $this->assertTrue(Role::where('name', 'Manager')->exists());
        $this->assertTrue(Role::where('name', 'Staff')->exists());
        $this->assertSame(85, Permission::count());

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole('Admin'));
        $this->assertTrue($admin->can('dashboard.view'));
        $this->assertTrue($admin->can('settings.edit'));
        $this->assertTrue($admin->can('backup.delete'));
        $this->assertTrue(Branch::where('branch_code', 'MAIN')->exists());
        $this->assertSame(5, PaymentMode::count());
        $this->assertSame(26, ShopSetting::count());
    }

    public function test_role_assignment_and_permission_checking(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::where('email', 'manager@example.com')->firstOrFail();

        $staff = User::where('email', 'staff@example.com')->firstOrFail();

        $this->assertTrue($manager->can('reports.export_excel'));
        $this->assertTrue($manager->can('settings.edit'));
        $this->assertFalse($manager->can('settings.backup'));
        $this->assertFalse($manager->can('backup.delete'));
        $this->assertFalse($manager->can('staff.delete'));

        $this->assertTrue($staff->can('payments.create'));
        $this->assertTrue($staff->can('receipts.pdf'));
        $this->assertTrue($staff->can('messages.send'));
        $this->assertFalse($staff->can('payments.edit'));
        $this->assertFalse($staff->can('settings.edit'));
        $this->assertFalse($staff->can('reports.view'));
    }
}
