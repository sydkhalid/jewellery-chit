<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitLedger;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_customer(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('customers.store'), $this->customerPayload([
            'mobile' => '9000000001',
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Customer created successfully')
            ->assertJsonPath('data.customer.mobile', '9000000001');

        $this->assertDatabaseHas('customers', [
            'mobile' => '9000000001',
            'customer_code' => 'CUS000001',
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('nominees', [
            'name' => 'Nominee One',
            'relationship' => 'Spouse',
        ]);
        $this->assertSame(1, ActivityLog::where('module', 'customers')->count());
        $this->assertSame(1, AuditLog::where('event', 'create')->count());
    }

    public function test_staff_can_create_customer_if_permission_exists(): void
    {
        $staff = User::where('email', 'staff@example.com')->firstOrFail();

        $response = $this->actingAs($staff)->postJson(route('customers.store'), $this->customerPayload([
            'mobile' => '9000000002',
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customers', [
            'mobile' => '9000000002',
            'created_by' => $staff->id,
        ]);
    }

    public function test_duplicate_mobile_validation_works(): void
    {
        $this->createCustomer(['mobile' => '9000000003']);

        $response = $this->actingAs($this->admin())->postJson(route('customers.store'), $this->customerPayload([
            'mobile' => '9000000003',
        ]));

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.mobile.0', 'The mobile has already been taken.');
    }

    public function test_customer_update_works(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000004']);

        $response = $this->actingAs($this->admin())->putJson(route('customers.update', $customer), $this->customerPayload([
            'name' => 'Updated Customer',
            'mobile' => '9000000004',
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.name', 'Updated Customer');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'update',
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
        ]);
    }

    public function test_customer_deactivate_works(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000005']);

        $response = $this->actingAs($this->admin())->patchJson(route('customers.deactivate', $customer));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deactivate',
            'auditable_id' => $customer->id,
        ]);
    }

    public function test_customer_delete_blocked_when_active_chit_exists(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000006']);
        $this->createEnrollment($customer);

        $response = $this->actingAs($this->admin())->deleteJson(route('customers.destroy', $customer));

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.customer.0', 'Customer has chit enrollments. Deactivate the customer instead of deleting.');

        $this->assertFalse($customer->fresh()->trashed());
    }

    public function test_document_upload_works(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer(['mobile' => '9000000007']);

        $response = $this->actingAs($this->admin())->postJson(route('customers.documents.store', $customer), [
            'document_type' => 'aadhaar',
            'document_number' => '123456789012',
            'file_path' => UploadedFile::fake()->image('aadhaar.jpg'),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Customer document uploaded successfully');

        $document = $customer->documents()->firstOrFail();
        Storage::disk('public')->assertExists($document->file_path);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'document_upload',
            'auditable_type' => \App\Models\CustomerDocument::class,
            'auditable_id' => $document->id,
        ]);
    }

    public function test_customer_profile_shows_correct_data(): void
    {
        $customer = $this->createCustomer([
            'name' => 'Profile Customer',
            'mobile' => '9000000008',
        ]);

        $response = $this->actingAs($this->admin())->get(route('customers.show', $customer));

        $response->assertOk()
            ->assertSee('Profile Customer')
            ->assertSee($customer->customer_code)
            ->assertSee('Nominee One')
            ->assertSee('Active Chit Accounts');
    }

    public function test_ledger_page_loads(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000009']);
        $enrollment = $this->createEnrollment($customer);

        ChitLedger::create([
            'enrollment_id' => $enrollment->id,
            'customer_id' => $customer->id,
            'transaction_date' => now(),
            'transaction_type' => 'payment',
            'debit' => 0,
            'credit' => 500,
            'balance' => 500,
            'remarks' => 'Opening payment',
            'created_by' => $this->admin()->id,
        ]);

        $response = $this->actingAs($this->admin())->get(route('customers.ledger', $customer));

        $response->assertOk()
            ->assertSee('Opening payment')
            ->assertSee('Closing Balance');
    }

    public function test_customer_datatable_returns_rows(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000010']);

        $response = $this->actingAs($this->admin())->getJson(route('customers.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.customer_code', $customer->customer_code)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);
    }

    public function test_api_customer_list_works(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000011']);
        Sanctum::actingAs($this->admin());

        $response = $this->getJson('/api/customers');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Customers fetched successfully')
            ->assertJsonPath('data.customers.0.customer_code', $customer->customer_code);
    }

    public function test_api_customer_create_works(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson('/api/customers', $this->customerPayload([
            'mobile' => '9000000012',
        ]));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Customer created successfully')
            ->assertJsonPath('data.customer.mobile', '9000000012');
    }

    public function test_api_customer_view_uses_consistent_response_format(): void
    {
        $customer = $this->createCustomer(['mobile' => '9000000013']);
        Sanctum::actingAs($this->admin());

        $response = $this->getJson('/api/customers/'.$customer->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Customer fetched successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customer' => [
                        'id',
                        'customer_code',
                        'name',
                        'mobile',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function customerPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Test Customer',
            'mobile' => '9000000000',
            'alternate_mobile' => '9000000099',
            'email' => 'customer@example.com',
            'aadhaar_no' => '123456789012',
            'pan_no' => 'ABCDE1234F',
            'address' => '12 Gold Street',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
            'nominee' => [
                'name' => 'Nominee One',
                'relationship' => 'Spouse',
                'mobile' => '9111111111',
                'address' => '12 Gold Street',
                'aadhaar_no' => '222222222222',
            ],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(array $overrides = []): Customer
    {
        $customer = Customer::create(array_merge([
            'customer_code' => 'CUS'.str_pad((string) (Customer::count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => 'Existing Customer',
            'mobile' => '9999900000',
            'address' => 'Existing address',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
            'status' => 'active',
        ], $overrides));

        $customer->nominee()->create([
            'name' => 'Nominee One',
            'relationship' => 'Spouse',
            'mobile' => '9111111111',
            'address' => '12 Gold Street',
            'aadhaar_no' => '222222222222',
        ]);

        return $customer;
    }

    private function createEnrollment(Customer $customer): ChitEnrollment
    {
        $scheme = ChitScheme::create([
            'scheme_code' => 'SCH'.str_pad((string) (ChitScheme::count() + 1), 4, '0', STR_PAD_LEFT),
            'name' => 'Gold Monthly',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'duration_months' => 12,
            'shop_bonus_type' => 'none',
            'shop_bonus_value' => 0,
            'grace_period_days' => 0,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'status' => 'active',
        ]);

        return ChitEnrollment::create([
            'chit_no' => 'CHIT'.str_pad((string) (ChitEnrollment::count() + 1), 5, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'scheme_id' => $scheme->id,
            'branch_id' => Branch::where('branch_code', 'MAIN')->value('id'),
            'assigned_staff_id' => User::where('email', 'staff@example.com')->value('id'),
            'start_date' => now()->toDateString(),
            'monthly_due_date' => 5,
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'total_months' => 12,
            'monthly_amount' => 1000,
            'total_payable' => 12000,
            'total_paid' => 1000,
            'total_pending' => 11000,
            'status' => 'active',
            'created_by' => $this->admin()->id,
        ]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }
}
