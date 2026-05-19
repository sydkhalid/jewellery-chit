<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminPanelBrowserTest extends DuskTestCase
{
    public function test_admin_login_dashboard_sidebar_customer_and_report_pages_load(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/login')
                ->type('email', 'admin@example.com')
                ->type('password', 'password')
                ->press('Sign in')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                ->assertSee('Dashboard')
                ->press('Customers')
                ->waitForText('Customer List')
                ->clickLink('Add Customer')
                ->assertPathIs('/customers/create')
                ->assertSee('New Customer')
                ->visit('/reports/customers')
                ->assertSee('Customer Report');
        });
    }

    public function test_payment_and_receipt_surfaces_are_reachable(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(1)
                ->visit('/payments/create')
                ->assertSee('Payment')
                ->visit('/receipts')
                ->assertSee('Receipt');
        });
    }
}
