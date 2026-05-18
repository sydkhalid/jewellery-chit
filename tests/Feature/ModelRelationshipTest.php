<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitPayment;
use App\Models\ChitReceipt;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_model_relationships_scopes_and_accessors_work(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR001',
            'name' => 'Main Branch',
            'address' => 'Market Road',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
            'status' => 'active',
        ]);

        $staff = User::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUS001',
            'name' => 'Lakshmi',
            'mobile' => '9000000001',
            'address' => 'North Street',
            'city' => 'Madurai',
            'state' => 'Tamil Nadu',
            'pincode' => '625001',
            'status' => 'active',
        ]);

        $scheme = ChitScheme::create([
            'scheme_code' => 'SCH001',
            'name' => 'Gold 12M',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'duration_months' => 12,
            'shop_bonus_type' => 'none',
            'late_fee_type' => 'none',
            'status' => 'active',
        ]);

        $enrollment = ChitEnrollment::create([
            'chit_no' => 'CHIT001',
            'customer_id' => $customer->id,
            'scheme_id' => $scheme->id,
            'branch_id' => $branch->id,
            'assigned_staff_id' => $staff->id,
            'start_date' => '2026-05-01',
            'monthly_due_date' => 10,
            'maturity_date' => '2027-05-01',
            'total_months' => 12,
            'monthly_amount' => 1000,
            'total_payable' => 12000,
            'total_paid' => 1000,
            'total_pending' => 11000,
            'status' => 'active',
        ]);

        $installment = ChitInstallment::create([
            'enrollment_id' => $enrollment->id,
            'installment_no' => 1,
            'due_date' => '2026-05-10',
            'due_amount' => 1000,
            'paid_amount' => 500,
            'balance_amount' => 500,
            'late_fee' => 0,
            'status' => 'partial',
        ]);

        $paymentMode = PaymentMode::create([
            'name' => 'Cash',
            'code' => 'cash',
            'status' => 'active',
        ]);

        $payment = ChitPayment::create([
            'payment_no' => 'pay001',
            'enrollment_id' => $enrollment->id,
            'customer_id' => $customer->id,
            'installment_id' => $installment->id,
            'payment_mode_id' => $paymentMode->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'payment_date' => '2026-05-10',
            'amount' => 500,
            'total_amount' => 500,
            'status' => 'success',
            'created_by' => $staff->id,
        ]);

        $receipt = ChitReceipt::create([
            'receipt_no' => 'rcpt001',
            'payment_id' => $payment->id,
            'enrollment_id' => $enrollment->id,
            'customer_id' => $customer->id,
            'receipt_date' => '2026-05-10',
            'amount' => 500,
            'status' => 'active',
        ]);

        AuditLog::create([
            'user_id' => $staff->id,
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
            'event' => 'create',
            'old_values' => null,
            'new_values' => ['name' => 'Lakshmi'],
        ]);

        $this->assertTrue($staff->branch->is($branch));
        $this->assertTrue($customer->enrollments->first()->is($enrollment));
        $this->assertTrue($enrollment->customer->is($customer));
        $this->assertTrue($enrollment->scheme->is($scheme));
        $this->assertTrue($enrollment->assignedStaff->is($staff));
        $this->assertTrue($installment->enrollment->is($enrollment));
        $this->assertTrue($payment->receipt->is($receipt));
        $this->assertTrue($paymentMode->payments->first()->is($payment));
        $this->assertTrue($staff->staffCollections->first()->is($payment));
        $this->assertTrue($staff->auditLogs->first()->auditable->is($customer));

        $this->assertSame('North Street, Madurai, Tamil Nadu, 625001', $customer->full_address);
        $this->assertSame(11000.0, $enrollment->balance_amount);
        $this->assertSame('running', $enrollment->maturity_status);
        $this->assertSame(500.0, $installment->balance_amount);
        $this->assertSame('PAY001', $payment->formatted_payment_no);
        $this->assertSame('RCPT001', $receipt->formatted_receipt_no);

        $this->assertSame(1, Customer::active()->count());
        $this->assertSame(1, ChitEnrollment::staffWise($staff->id)->count());
        $this->assertSame(1, ChitPayment::branchWise($branch->id)->count());
        $this->assertSame(1, ChitPayment::betweenDates('2026-05-01', '2026-05-31', 'payment_date')->count());
    }

    public function test_shop_setting_helpers_cast_values_by_type(): void
    {
        ShopSetting::updateByKey('invoice.prefix', 'INV', 'text', 'billing');
        ShopSetting::updateByKey('billing.tax_enabled', true, 'boolean', 'billing');
        ShopSetting::updateByKey('billing.options', ['rounding' => true], 'json', 'billing');

        $this->assertSame('INV', ShopSetting::getByKey('invoice.prefix'));
        $this->assertTrue(ShopSetting::getByKey('billing.tax_enabled'));
        $this->assertSame(['rounding' => true], ShopSetting::getByKey('billing.options'));
        $this->assertSame('fallback', ShopSetting::getByKey('missing.key', 'fallback'));
    }
}
