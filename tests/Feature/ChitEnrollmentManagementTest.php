<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitPayment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChitEnrollmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_enrollment(): void
    {
        $customer = $this->createCustomer();
        $scheme = $this->createScheme();

        $response = $this->actingAs($this->admin())->postJson(route('chit-enrollments.store'), $this->enrollmentPayload($customer, $scheme));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Enrollment created successfully')
            ->assertJsonPath('data.enrollment.chit_no', 'CHIT000001');

        $this->assertDatabaseHas('chit_enrollments', [
            'chit_no' => 'CHIT000001',
            'customer_id' => $customer->id,
            'scheme_id' => $scheme->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'create',
            'auditable_type' => ChitEnrollment::class,
        ]);
    }

    public function test_staff_can_create_enrollment_if_permission_exists(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000102']);
        $scheme = $this->createScheme(['scheme_code' => 'SCHT002']);
        $staff = User::where('email', 'staff@example.com')->firstOrFail();

        $response = $this->actingAs($staff)->postJson(route('chit-enrollments.store'), $this->enrollmentPayload($customer, $scheme));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('chit_enrollments', [
            'customer_id' => $customer->id,
            'created_by' => $staff->id,
        ]);
    }

    public function test_only_active_schemes_are_selectable(): void
    {
        $active = $this->createScheme(['name' => 'Selectable Scheme', 'scheme_code' => 'SCHT003', 'status' => 'active']);
        $inactive = $this->createScheme(['name' => 'Inactive Scheme', 'scheme_code' => 'SCHT004', 'status' => 'inactive']);

        $response = $this->actingAs($this->admin())->get(route('chit-enrollments.create'));

        $response->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);
    }

    public function test_flexible_amount_validation_works(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000105']);
        $scheme = $this->createScheme([
            'scheme_code' => 'SCHT005',
            'scheme_type' => 'flexible_amount',
            'monthly_amount' => null,
            'min_amount' => 1000,
            'max_amount' => 5000,
        ]);

        $response = $this->actingAs($this->admin())->postJson(route('chit-enrollments.store'), $this->enrollmentPayload($customer, $scheme, [
            'monthly_amount' => 750,
        ]));

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.monthly_amount.0', 'Monthly amount must be between scheme minimum and maximum amount.');
    }

    public function test_chit_number_due_date_maturity_date_and_installments_are_created(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000106']);
        $scheme = $this->createScheme([
            'scheme_code' => 'SCHT006',
            'duration_months' => 6,
            'monthly_amount' => 2000,
        ]);

        $this->actingAs($this->admin())->postJson(route('chit-enrollments.store'), $this->enrollmentPayload($customer, $scheme, [
            'start_date' => '2026-05-18',
        ]))->assertOk();

        $enrollment = ChitEnrollment::firstOrFail();

        $this->assertSame('CHIT000001', $enrollment->chit_no);
        $this->assertSame(18, $enrollment->monthly_due_date);
        $this->assertSame('2026-11-18', $enrollment->maturity_date->toDateString());
        $this->assertSame(6, $enrollment->installments()->count());
        $this->assertDatabaseHas('chit_installments', [
            'enrollment_id' => $enrollment->id,
            'installment_no' => 1,
            'due_date' => '2026-05-18',
            'due_amount' => 2000,
            'balance_amount' => 2000,
            'status' => 'pending',
        ]);
    }

    public function test_agreement_upload_works(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer(['mobile' => '9000000107']);
        $scheme = $this->createScheme(['scheme_code' => 'SCHT007']);

        $response = $this->actingAs($this->admin())->postJson(route('chit-enrollments.store'), $this->enrollmentPayload($customer, $scheme, [
            'agreement_file' => UploadedFile::fake()->create('agreement.pdf', 128, 'application/pdf'),
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $enrollment = ChitEnrollment::firstOrFail();
        Storage::disk('public')->assertExists($enrollment->agreement_file);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'agreement_upload',
            'auditable_type' => ChitEnrollment::class,
            'auditable_id' => $enrollment->id,
        ]);
    }

    public function test_enrollment_update_works(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000108']);
        $scheme = $this->createScheme(['scheme_code' => 'SCHT008']);
        $enrollment = $this->createEnrollment($customer, $scheme);

        $response = $this->actingAs($this->admin())->putJson(route('chit-enrollments.update', $enrollment), $this->enrollmentPayload($customer, $scheme, [
            'start_date' => '2026-06-10',
            'remarks' => 'Updated enrollment',
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.enrollment.remarks', 'Updated enrollment');

        $this->assertDatabaseHas('chit_enrollments', [
            'id' => $enrollment->id,
            'monthly_due_date' => 10,
            'remarks' => 'Updated enrollment',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'update',
            'auditable_id' => $enrollment->id,
        ]);
    }

    public function test_delete_blocked_if_payment_exists(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000109']);
        $scheme = $this->createScheme(['scheme_code' => 'SCHT009']);
        $enrollment = $this->createEnrollment($customer, $scheme);
        $installment = $this->createInstallment($enrollment);
        $this->createPayment($enrollment, $customer, $installment);

        $response = $this->actingAs($this->admin())->deleteJson(route('chit-enrollments.destroy', $enrollment));

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.enrollment.0', 'Enrollment has payments and cannot be deleted. Cancel the enrollment instead.');

        $this->assertFalse($enrollment->fresh()->trashed());
    }

    public function test_cancel_enrollment_works(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000110']);
        $scheme = $this->createScheme(['scheme_code' => 'SCHT010']);
        $enrollment = $this->createEnrollment($customer, $scheme);

        $response = $this->actingAs($this->admin())->postJson(route('chit-enrollments.cancel', $enrollment), [
            'cancellation_date' => '2026-05-20',
            'reason' => 'Customer request',
            'refund_amount' => 500,
            'deduction_amount' => 100,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.enrollment.status', 'cancelled');

        $this->assertDatabaseHas('chit_cancellations', [
            'enrollment_id' => $enrollment->id,
            'reason' => 'Customer request',
            'refund_amount' => 500,
            'deduction_amount' => 100,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cancellation',
            'auditable_id' => $enrollment->id,
        ]);
    }

    public function test_enrollment_datatables_loads_correctly(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000111']);
        $scheme = $this->createScheme(['scheme_code' => 'SCHT011']);
        $enrollment = $this->createEnrollment($customer, $scheme);

        $response = $this->actingAs($this->admin())->getJson(route('chit-enrollments.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.chit_no', $enrollment->chit_no)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);
    }

    public function test_api_enrollment_list_create_view_and_response_format_work(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000112']);
        $scheme = $this->createScheme(['scheme_code' => 'SCHT012']);
        Sanctum::actingAs($this->admin());

        $createResponse = $this->postJson('/api/chit-enrollments', $this->enrollmentPayload($customer, $scheme));

        $createResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Enrollment created successfully')
            ->assertJsonPath('data.enrollment.chit_no', 'CHIT000001');

        $enrollment = ChitEnrollment::firstOrFail();

        $this->getJson('/api/chit-enrollments')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Enrollments fetched successfully')
            ->assertJsonPath('data.enrollments.0.chit_no', $enrollment->chit_no);

        $this->getJson('/api/chit-enrollments/'.$enrollment->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Enrollment fetched successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'enrollment' => [
                        'id',
                        'chit_no',
                        'customer',
                        'scheme',
                        'start_date',
                        'monthly_due_date',
                        'maturity_date',
                        'status',
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
            'customer_code' => 'CUS'.str_pad((string) (Customer::count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => 'Enrollment Customer',
            'mobile' => '9000000101',
            'address' => 'Enrollment address',
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
            'scheme_code' => 'SCHT001',
            'name' => 'Enrollment Scheme',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'min_amount' => null,
            'max_amount' => null,
            'gold_weight' => null,
            'duration_months' => 12,
            'shop_bonus_type' => 'none',
            'shop_bonus_value' => 0,
            'grace_period_days' => 0,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'status' => 'active',
        ], $overrides));
    }

    private function createEnrollment(Customer $customer, ChitScheme $scheme): ChitEnrollment
    {
        return ChitEnrollment::create([
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
    }

    private function createInstallment(ChitEnrollment $enrollment): ChitInstallment
    {
        return ChitInstallment::create([
            'enrollment_id' => $enrollment->id,
            'installment_no' => 1,
            'due_date' => '2026-05-18',
            'due_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'late_fee' => 0,
            'status' => 'pending',
        ]);
    }

    private function createPayment(ChitEnrollment $enrollment, Customer $customer, ChitInstallment $installment): ChitPayment
    {
        return ChitPayment::create([
            'payment_no' => 'PAY000001',
            'enrollment_id' => $enrollment->id,
            'customer_id' => $customer->id,
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
            'monthly_amount' => $scheme->scheme_type === 'flexible_amount' ? $scheme->min_amount : null,
            'remarks' => 'Enrollment test remarks',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }
}
