<?php

namespace Tests\Feature;

use App\Models\ChitEnrollment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GoLiveSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_data_import_command_imports_customers_and_chits(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        ChitScheme::query()->create([
            'scheme_code' => 'SCH-LIVE-TEST',
            'name' => 'Live Test Scheme',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'duration_months' => 3,
            'shop_bonus_type' => 'none',
            'shop_bonus_value' => 0,
            'grace_period_days' => 0,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'maturity_rule' => 'Test rule',
            'early_closing_rule' => 'Test rule',
            'status' => 'active',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $customerPath = storage_path('app/testing-live-customers.csv');
        $chitPath = storage_path('app/testing-live-chits.csv');
        File::put($customerPath, implode(PHP_EOL, [
            'customer_code,name,mobile,address,city,state,pincode,status,nominee_name,nominee_relationship',
            'CUS-LIVE-TEST,Live Customer,9555555555,Street,Chennai,Tamil Nadu,600001,active,Live Nominee,Family',
        ]));
        File::put($chitPath, implode(PHP_EOL, [
            'chit_no,customer_code,scheme_code,branch_code,assigned_staff_email,start_date,total_months,monthly_amount,total_payable,total_paid,last_paid_date,status',
            'CHIT-LIVE-TEST,CUS-LIVE-TEST,SCH-LIVE-TEST,MAIN,staff@example.com,2026-05-01,3,1000,3000,1000,2026-05-10,active',
        ]));

        try {
            $exitCode = Artisan::call('live-data:import', [
                '--customers' => $customerPath,
                '--chits' => $chitPath,
            ]);

            $this->assertSame(0, $exitCode);
            $this->assertDatabaseHas('customers', [
                'customer_code' => 'CUS-LIVE-TEST',
                'mobile' => '9555555555',
            ]);

            $customer = Customer::query()->where('customer_code', 'CUS-LIVE-TEST')->firstOrFail();
            $this->assertSame('Live Nominee', $customer->nominee()->first()?->name);

            $enrollment = ChitEnrollment::query()->where('chit_no', 'CHIT-LIVE-TEST')->firstOrFail();
            $this->assertSame(3, $enrollment->installments()->count());
            $this->assertDatabaseHas('chit_ledgers', [
                'enrollment_id' => $enrollment->id,
                'transaction_type' => 'opening_balance',
                'credit' => 1000,
            ]);
        } finally {
            File::delete($customerPath);
            File::delete($chitPath);
        }
    }

    public function test_go_live_verify_command_runs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $exitCode = Artisan::call('go-live:verify');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Go-live readiness:', Artisan::output());
    }
}
