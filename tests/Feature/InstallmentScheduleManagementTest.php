<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitPayment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\User;
use App\Services\InstallmentService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstallmentScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-18 10:00:00');
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_schedule_auto_generates_after_enrollment(): void
    {
        $customer = $this->createCustomer();
        $scheme = $this->createScheme([
            'duration_months' => 4,
            'monthly_amount' => 1500,
        ]);

        $response = $this->actingAs($this->admin())->postJson(route('chit-enrollments.store'), $this->enrollmentPayload($customer, $scheme, [
            'start_date' => '2026-05-18',
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $enrollment = ChitEnrollment::firstOrFail();

        $this->assertSame(4, $enrollment->installments()->count());
        $this->assertDatabaseHas('chit_installments', [
            'enrollment_id' => $enrollment->id,
            'installment_no' => 1,
            'due_date' => '2026-05-18',
            'due_amount' => 1500,
            'paid_amount' => 0,
            'balance_amount' => 1500,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('chit_installments', [
            'enrollment_id' => $enrollment->id,
            'installment_no' => 4,
            'due_date' => '2026-08-18',
            'due_amount' => 1500,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'schedule_generation',
            'auditable_type' => ChitEnrollment::class,
            'auditable_id' => $enrollment->id,
        ]);
    }

    public function test_overdue_status_updates_correctly(): void
    {
        $enrollment = $this->createEnrollmentWithInstallment([
            'due_date' => '2026-05-01',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin())->postJson(route('installments.mark-overdue'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 1);

        $this->assertSame('overdue', $enrollment->installments()->firstOrFail()->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'status_update',
            'auditable_type' => ChitInstallment::class,
        ]);
    }

    public function test_partial_and_paid_status_work(): void
    {
        $enrollment = $this->createEnrollmentWithInstallment([
            'due_date' => '2026-05-20',
        ]);
        $installment = $enrollment->installments()->firstOrFail();

        $installment->update([
            'paid_amount' => 500,
            'balance_amount' => 500,
        ]);

        app(InstallmentService::class)->updateInstallmentStatus($installment->refresh());
        $this->assertSame('partial', $installment->refresh()->status);

        $installment->update([
            'paid_amount' => 1000,
            'balance_amount' => 0,
        ]);

        app(InstallmentService::class)->updateInstallmentStatus($installment->refresh());
        $this->assertSame('paid', $installment->refresh()->status);
        $this->assertSame('2026-05-18', $installment->paid_date->toDateString());
    }

    public function test_advance_payment_updates_future_installments(): void
    {
        $enrollment = $this->createEnrollmentWithInstallment();
        ChitInstallment::create([
            'enrollment_id' => $enrollment->id,
            'installment_no' => 2,
            'due_date' => '2026-06-18',
            'due_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'late_fee' => 0,
            'status' => 'pending',
        ]);
        ChitInstallment::create([
            'enrollment_id' => $enrollment->id,
            'installment_no' => 3,
            'due_date' => '2026-07-18',
            'due_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'late_fee' => 0,
            'status' => 'pending',
        ]);

        $firstInstallment = $enrollment->installments()->where('installment_no', 1)->firstOrFail();
        $firstInstallment->update([
            'paid_amount' => 2500,
            'balance_amount' => 0,
            'status' => 'advance',
        ]);

        app(InstallmentService::class)->updateInstallmentStatus($firstInstallment->refresh());

        $firstInstallment->refresh();
        $secondInstallment = $enrollment->installments()->where('installment_no', 2)->firstOrFail();
        $thirdInstallment = $enrollment->installments()->where('installment_no', 3)->firstOrFail();

        $this->assertSame('paid', $firstInstallment->status);
        $this->assertSame('1000.00', $firstInstallment->paid_amount);
        $this->assertSame('paid', $secondInstallment->status);
        $this->assertSame('1000.00', $secondInstallment->paid_amount);
        $this->assertSame('partial', $thirdInstallment->status);
        $this->assertSame('500.00', $thirdInstallment->paid_amount);
        $this->assertSame(500.0, $thirdInstallment->balance_amount);
    }

    public function test_regeneration_is_blocked_when_payments_exist(): void
    {
        $enrollment = $this->createEnrollmentWithInstallment();
        $installment = $enrollment->installments()->firstOrFail();
        $this->createPayment($enrollment, $installment);

        $response = $this->actingAs($this->admin())->postJson(route('chit-enrollments.installments.regenerate', $enrollment));

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.enrollment.0', 'Installment schedule cannot be regenerated after payments exist.');

        $this->assertSame(1, $enrollment->installments()->count());
    }

    public function test_installment_datatables_loads_correctly(): void
    {
        $enrollment = $this->createEnrollmentWithInstallment();
        $installment = $enrollment->installments()->firstOrFail();

        $response = $this->actingAs($this->admin())->getJson(route('installments.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.chit_no', $enrollment->chit_no)
            ->assertJsonPath('data.0.installment_no', $installment->installment_no)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);
    }

    public function test_api_installment_list_and_enrollment_installments_work(): void
    {
        $enrollment = $this->createEnrollmentWithInstallment();
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/installments')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Installments fetched successfully')
            ->assertJsonPath('data.0.enrollment.chit_no', $enrollment->chit_no);

        $this->getJson('/api/chit-enrollments/'.$enrollment->id.'/installments')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Installments fetched successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'enrollment' => [
                        'id',
                        'chit_no',
                    ],
                    'installments' => [
                        '*' => [
                            'id',
                            'installment_no',
                            'due_date',
                            'due_amount',
                            'paid_amount',
                            'balance_amount',
                            'status',
                        ],
                    ],
                ],
            ]);
    }

    public function test_installment_update_uses_validation_and_service_logic(): void
    {
        $enrollment = $this->createEnrollmentWithInstallment();
        $installment = $enrollment->installments()->firstOrFail();

        $response = $this->actingAs($this->admin())->putJson(route('installments.update', $installment), [
            'due_date' => '2026-05-25',
            'due_amount' => 1200,
            'late_fee' => 50,
            'status' => 'pending',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Installment updated successfully')
            ->assertJsonPath('data.installment.due_amount', '1200.00');

        $installment->refresh();
        $this->assertSame('2026-05-25', $installment->due_date->toDateString());
        $this->assertSame('1200.00', $installment->due_amount);
        $this->assertSame('50.00', $installment->late_fee);
        $this->assertSame(1250.0, $installment->balance_amount);
        $this->assertSame('pending', $installment->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'update',
            'auditable_type' => ChitInstallment::class,
            'auditable_id' => $installment->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'customer_code' => 'CUS'.str_pad((string) (Customer::count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => 'Installment Customer',
            'mobile' => '9010000001',
            'address' => 'Installment address',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
            'status' => 'active',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createScheme(array $overrides = []): ChitScheme
    {
        return ChitScheme::create(array_merge([
            'scheme_code' => 'INSTSCH'.str_pad((string) (ChitScheme::count() + 1), 3, '0', STR_PAD_LEFT),
            'name' => 'Installment Scheme',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'duration_months' => 12,
            'shop_bonus_type' => 'none',
            'shop_bonus_value' => 0,
            'grace_period_days' => 0,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'status' => 'active',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $installmentOverrides
     */
    private function createEnrollmentWithInstallment(array $installmentOverrides = []): ChitEnrollment
    {
        $customer = $this->createCustomer([
            'mobile' => '901000'.str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
        ]);
        $scheme = $this->createScheme();
        $enrollment = ChitEnrollment::create([
            'chit_no' => 'CHIT'.str_pad((string) (ChitEnrollment::withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'scheme_id' => $scheme->id,
            'branch_id' => Branch::where('branch_code', 'MAIN')->value('id'),
            'assigned_staff_id' => User::where('email', 'staff@example.com')->value('id'),
            'start_date' => '2026-05-18',
            'monthly_due_date' => 18,
            'maturity_date' => '2027-05-18',
            'total_months' => 12,
            'monthly_amount' => 1000,
            'total_payable' => 12000,
            'total_paid' => 0,
            'total_pending' => 12000,
            'status' => 'active',
            'created_by' => $this->admin()->id,
        ]);

        ChitInstallment::create(array_merge([
            'enrollment_id' => $enrollment->id,
            'installment_no' => 1,
            'due_date' => '2026-05-18',
            'due_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'late_fee' => 0,
            'status' => 'pending',
        ], $installmentOverrides));

        return $enrollment->refresh();
    }

    private function createPayment(ChitEnrollment $enrollment, ChitInstallment $installment): ChitPayment
    {
        return ChitPayment::create([
            'payment_no' => 'PAY'.str_pad((string) (ChitPayment::withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT),
            'enrollment_id' => $enrollment->id,
            'customer_id' => $enrollment->customer_id,
            'installment_id' => $installment->id,
            'payment_mode_id' => PaymentMode::where('code', 'cash')->value('id'),
            'branch_id' => $enrollment->branch_id,
            'staff_id' => $enrollment->assigned_staff_id,
            'payment_date' => '2026-05-18',
            'amount' => 1000,
            'late_fee_amount' => 0,
            'total_amount' => 1000,
            'status' => 'success',
            'created_by' => $this->admin()->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function enrollmentPayload(Customer $customer, ChitScheme $scheme, array $overrides = []): array
    {
        return array_replace([
            'customer_id' => $customer->id,
            'scheme_id' => $scheme->id,
            'branch_id' => Branch::where('branch_code', 'MAIN')->value('id'),
            'assigned_staff_id' => User::where('email', 'staff@example.com')->value('id'),
            'start_date' => '2026-05-18',
            'monthly_amount' => null,
            'remarks' => 'Installment test remarks',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }
}
