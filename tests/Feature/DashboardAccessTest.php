<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Welcome, Admin')
            ->assertSee('Role:')
            ->assertSee('Admin')
            ->assertSee('Total customers')
            ->assertSee('Active chits')
            ->assertSee('Today collection')
            ->assertSee('Monthly collection')
            ->assertSee('Pending dues')
            ->assertSee('Matured chits')
            ->assertSee('Closed chits')
            ->assertSee('Overdue customers');
    }

    public function test_manager_can_access_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::factory()->create(['name' => 'Manager User']);
        $manager->assignRole('Manager');

        $this->actingAs($manager)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Manager');
    }

    public function test_staff_can_access_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $staff = User::factory()->create(['name' => 'Staff User']);
        $staff->assignRole('Staff');

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Staff');
    }

    public function test_user_without_admin_or_staff_role_cannot_access_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_admin_sidebar_can_see_all_parent_menus(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $response = $this->actingAs($admin)->get('/dashboard');

        foreach ([
            'Dashboard',
            'Customers',
            'Chit Schemes',
            'Chit Enrollments',
            'Installments',
            'Payments',
            'Receipts',
            'Ledger',
            'Pending Dues',
            'Maturity Closing',
            'Jewellery Billing',
            'Gold Rates',
            'Staff & Branch',
            'Cashflow',
            'Reports',
            'WhatsApp/SMS',
            'Admin Settings',
        ] as $menuLabel) {
            $response->assertSee($menuLabel);
        }
    }

    public function test_staff_sidebar_only_shows_assigned_parent_menus(): void
    {
        $this->seed(DatabaseSeeder::class);

        $staff = User::factory()->create(['name' => 'Staff User']);
        $staff->assignRole('Staff');

        $response = $this->actingAs($staff)->get('/dashboard');

        $response->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Customers')
            ->assertSee('Payments')
            ->assertSee('Receipts')
            ->assertSee('Ledger')
            ->assertSee('Pending Dues')
            ->assertSee('WhatsApp/SMS')
            ->assertSee('Message Dashboard')
            ->assertDontSee('WhatsApp Logs')
            ->assertDontSee('SMS Logs')
            ->assertDontSee('Gold Rates')
            ->assertDontSee('Staff & Branch')
            ->assertDontSee('Reports')
            ->assertDontSee('Admin Settings');
    }

    public function test_staff_with_send_permission_can_open_message_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $staff = User::factory()->create(['name' => 'Staff User']);
        $staff->assignRole('Staff');

        $this->actingAs($staff)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Send Message')
            ->assertDontSee('WhatsApp Logs')
            ->assertDontSee('SMS Logs');
    }

    public function test_dashboard_contains_chart_mount_points_and_responsive_shell(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="staffWiseCollectionChart"', false)
            ->assertSee('id="schemeWiseCollectionChart"', false)
            ->assertSee('id="monthlyCollectionTrendChart"', false)
            ->assertSee('id="paymentModeCollectionChart"', false)
            ->assertSee('id="dashboard-chart-data"', false)
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('admin-sidebar-backdrop', false);
    }
}
