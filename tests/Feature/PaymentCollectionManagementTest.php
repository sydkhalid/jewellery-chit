<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cashbook;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitLedger;
use App\Models\ChitPayment;
use App\Models\ChitReceipt;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentCollectionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-19 10:00:00');
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_collect_payment_and_related_records_are_created(): void
    {
        $enrollment = $this->createEnrollmentWithInstallments(months: 1);
        $installment = $enrollment->installments()->firstOrFail();

        $response = $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($enrollment, [
            'installment_id' => $installment->id,
            'payment_type' => 'full',
            'amount' => 1000,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Payment collected successfully')
            ->assertJsonPath('data.payment.payment_no', 'PAY000001');

        $payment = ChitPayment::firstOrFail();
        $this->assertDatabaseHas('chit_payment_allocations', [
            'payment_id' => $payment->id,
            'installment_id' => $installment->id,
            'amount' => 1000,
        ]);
        $this->assertDatabaseHas('chit_receipts', [
            'payment_id' => $payment->id,
            'receipt_no' => 'RCPT000001',
            'amount' => 1000,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('chit_ledgers', [
            'reference_type' => ChitPayment::class,
            'reference_id' => $payment->id,
            'transaction_type' => 'payment',
            'credit' => 1000,
        ]);
        $this->assertDatabaseHas('cashbooks', [
            'reference_type' => ChitPayment::class,
            'reference_id' => $payment->id,
            'transaction_type' => 'cash_received',
            'credit' => 1000,
        ]);
        $this->assertSame('paid', $installment->refresh()->status);
        $this->assertSame('1000.00', $enrollment->refresh()->total_paid);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'payment',
            'auditable_type' => ChitPayment::class,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_staff_can_collect_payment_if_permission_exists(): void
    {
        $enrollment = $this->createEnrollmentWithInstallments(months: 1);
        $staff = User::where('email', 'staff@example.com')->firstOrFail();

        $response = $this->actingAs($staff)->postJson(route('payments.store'), $this->paymentPayload($enrollment, [
            'payment_type' => 'partial',
            'amount' => 400,
            'staff_id' => $staff->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('chit_payments', [
            'staff_id' => $staff->id,
            'created_by' => $staff->id,
            'amount' => 400,
        ]);
    }

    public function test_partial_advance_and_multiple_month_payments_update_installments(): void
    {
        $partialEnrollment = $this->createEnrollmentWithInstallments(months: 1, codeSuffix: 'P');
        $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($partialEnrollment, [
            'payment_type' => 'partial',
            'amount' => 400,
        ]))->assertOk();
        $partialInstallment = $partialEnrollment->installments()->firstOrFail();
        $this->assertSame('partial', $partialInstallment->refresh()->status);
        $this->assertSame('400.00', $partialInstallment->paid_amount);
        $this->assertSame(600.0, $partialInstallment->balance_amount);

        $advanceEnrollment = $this->createEnrollmentWithInstallments(months: 3, codeSuffix: 'A');
        $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($advanceEnrollment, [
            'payment_type' => 'advance',
            'amount' => 2500,
        ]))->assertOk();
        $this->assertSame('paid', $advanceEnrollment->installments()->where('installment_no', 1)->firstOrFail()->status);
        $this->assertSame('paid', $advanceEnrollment->installments()->where('installment_no', 2)->firstOrFail()->status);
        $third = $advanceEnrollment->installments()->where('installment_no', 3)->firstOrFail();
        $this->assertSame('partial', $third->status);
        $this->assertSame('500.00', $third->paid_amount);

        $multiEnrollment = $this->createEnrollmentWithInstallments(months: 3, codeSuffix: 'M');
        $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($multiEnrollment, [
            'payment_type' => 'multiple_month',
            'amount' => 2000,
        ]))->assertOk();
        $this->assertSame('paid', $multiEnrollment->installments()->where('installment_no', 1)->firstOrFail()->status);
        $this->assertSame('paid', $multiEnrollment->installments()->where('installment_no', 2)->firstOrFail()->status);
        $this->assertSame('pending', $multiEnrollment->installments()->where('installment_no', 3)->firstOrFail()->status);
    }

    public function test_late_fee_calculation_works(): void
    {
        $enrollment = $this->createEnrollmentWithInstallments(months: 1, codeSuffix: 'L', schemeOverrides: [
            'grace_period_days' => 0,
            'late_fee_type' => 'fixed',
            'late_fee_value' => 25,
        ], firstDueDate: '2026-05-01');

        $response = $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($enrollment, [
            'payment_type' => 'full',
            'amount' => 1000,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.payment.late_fee_amount', '25.00')
            ->assertJsonPath('data.payment.total_amount', '1025.00');

        $installment = $enrollment->installments()->firstOrFail();
        $this->assertSame('25.00', $installment->late_fee);
        $this->assertSame('1025.00', $installment->paid_amount);
    }

    public function test_payment_cancellation_reverses_effects(): void
    {
        $enrollment = $this->createEnrollmentWithInstallments(months: 1);
        $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($enrollment, [
            'payment_type' => 'full',
            'amount' => 1000,
        ]))->assertOk();

        $payment = ChitPayment::firstOrFail();
        $response = $this->actingAs($this->admin())->postJson(route('payments.cancel', $payment), [
            'cancellation_reason' => 'Wrong entry',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment.status', 'cancelled');

        $installment = $enrollment->installments()->firstOrFail();
        $this->assertSame('overdue', $installment->refresh()->status);
        $this->assertSame('0.00', $installment->paid_amount);
        $this->assertSame(1000.0, $installment->balance_amount);
        $this->assertSame('0.00', $enrollment->refresh()->total_paid);
        $this->assertSame('cancelled', ChitReceipt::firstOrFail()->status);
        $this->assertSame(2, ChitLedger::where('reference_id', $payment->id)->count());
        $this->assertSame(2, Cashbook::where('reference_id', $payment->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cancellation',
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_payment_edit_approval_works(): void
    {
        $enrollment = $this->createEnrollmentWithInstallments(months: 1);
        $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($enrollment, [
            'payment_type' => 'full',
            'amount' => 1000,
        ]))->assertOk();

        $payment = ChitPayment::firstOrFail();

        $this->actingAs($this->admin())->putJson(route('payments.update', $payment), $this->paymentPayload($enrollment, [
            'payment_type' => 'partial',
            'amount' => 500,
            'remarks' => 'Corrected amount',
        ]))->assertOk()
            ->assertJsonPath('message', 'Payment edit approval requested successfully')
            ->assertJsonPath('data.payment.edit_status', 'pending');

        $this->actingAs($this->admin())->postJson(route('payments.approve-edit', $payment), [
            'approved' => true,
        ])->assertOk()
            ->assertJsonPath('message', 'Payment edit approval processed successfully')
            ->assertJsonPath('data.payment.edit_status', 'approved')
            ->assertJsonPath('data.payment.amount', '500.00');

        $installment = $enrollment->installments()->firstOrFail();
        $this->assertSame('partial', $installment->refresh()->status);
        $this->assertSame('500.00', $installment->paid_amount);
        $this->assertSame('500.00', $enrollment->refresh()->total_paid);
    }

    public function test_payment_datatables_loads_correctly(): void
    {
        $enrollment = $this->createEnrollmentWithInstallments(months: 1);
        $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($enrollment, [
            'payment_type' => 'partial',
            'amount' => 300,
        ]))->assertOk();

        $payment = ChitPayment::firstOrFail();

        $response = $this->actingAs($this->admin())->getJson(route('payments.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.payment_no', $payment->payment_no)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);
    }

    public function test_api_payment_list_collection_view_and_response_format_work(): void
    {
        $enrollment = $this->createEnrollmentWithInstallments(months: 1);
        Sanctum::actingAs($this->admin());

        $createResponse = $this->postJson('/api/payments', $this->paymentPayload($enrollment, [
            'payment_type' => 'partial',
            'amount' => 600,
        ]));

        $createResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Payment collected successfully')
            ->assertJsonPath('data.payment.payment_no', 'PAY000001');

        $payment = ChitPayment::firstOrFail();

        $this->getJson('/api/payments')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Payments fetched successfully')
            ->assertJsonPath('data.0.payment_no', $payment->payment_no);

        $this->getJson('/api/payments/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Payment fetched successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'payment' => [
                        'id',
                        'payment_no',
                        'amount',
                        'receipt',
                        'allocations',
                    ],
                ],
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'customer_code' => 'PAYCUS'.str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
            'name' => 'Payment Customer',
            'mobile' => '902000'.str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
            'address' => 'Payment address',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
            'status' => 'active',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $schemeOverrides
     */
    private function createEnrollmentWithInstallments(int $months = 3, string $codeSuffix = 'X', array $schemeOverrides = [], string $firstDueDate = '2026-05-18'): ChitEnrollment
    {
        $customer = $this->createCustomer();
        $scheme = ChitScheme::create(array_merge([
            'scheme_code' => 'PAYSCH'.$codeSuffix.ChitScheme::count(),
            'name' => 'Payment Scheme '.$codeSuffix,
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'duration_months' => $months,
            'shop_bonus_type' => 'none',
            'shop_bonus_value' => 0,
            'grace_period_days' => 0,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'status' => 'active',
        ], $schemeOverrides));

        $enrollment = ChitEnrollment::create([
            'chit_no' => 'PAYCHIT'.str_pad((string) (ChitEnrollment::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'scheme_id' => $scheme->id,
            'branch_id' => Branch::where('branch_code', 'MAIN')->value('id'),
            'assigned_staff_id' => User::where('email', 'staff@example.com')->value('id'),
            'start_date' => $firstDueDate,
            'monthly_due_date' => (int) Carbon::parse($firstDueDate)->format('d'),
            'maturity_date' => Carbon::parse($firstDueDate)->addMonthsNoOverflow($months)->toDateString(),
            'total_months' => $months,
            'monthly_amount' => 1000,
            'total_payable' => $months * 1000,
            'total_paid' => 0,
            'total_pending' => $months * 1000,
            'status' => 'active',
            'created_by' => $this->admin()->id,
        ]);

        $start = Carbon::parse($firstDueDate);

        for ($i = 1; $i <= $months; $i++) {
            ChitInstallment::create([
                'enrollment_id' => $enrollment->id,
                'installment_no' => $i,
                'due_date' => $start->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                'due_amount' => 1000,
                'paid_amount' => 0,
                'balance_amount' => 1000,
                'late_fee' => 0,
                'status' => 'pending',
            ]);
        }

        return $enrollment->refresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paymentPayload(ChitEnrollment $enrollment, array $overrides = []): array
    {
        return array_replace([
            'enrollment_id' => $enrollment->id,
            'customer_id' => $enrollment->customer_id,
            'installment_id' => null,
            'payment_mode_id' => PaymentMode::where('code', 'cash')->value('id'),
            'branch_id' => $enrollment->branch_id,
            'staff_id' => $enrollment->assigned_staff_id,
            'payment_date' => '2026-05-19',
            'amount' => 1000,
            'remarks' => 'Payment test',
            'payment_type' => 'partial',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }
}
