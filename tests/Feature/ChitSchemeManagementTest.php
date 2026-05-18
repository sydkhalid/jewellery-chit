<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChitSchemeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_fixed_amount_scheme(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('chit-schemes.store'), $this->schemePayload([
            'name' => 'Gold Fixed 12M',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Scheme created successfully')
            ->assertJsonPath('data.scheme.name', 'Gold Fixed 12M')
            ->assertJsonPath('data.scheme.scheme_code', 'SCH00001');

        $this->assertDatabaseHas('chit_schemes', [
            'name' => 'Gold Fixed 12M',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'created_by' => $this->admin()->id,
        ]);
        $this->assertSame(1, ActivityLog::where('module', 'chit_schemes')->count());
        $this->assertSame(1, AuditLog::where('event', 'create')->where('auditable_type', ChitScheme::class)->count());
    }

    public function test_admin_can_create_flexible_amount_scheme(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('chit-schemes.store'), $this->schemePayload([
            'name' => 'Flexible Gold Plan',
            'scheme_type' => 'flexible_amount',
            'monthly_amount' => null,
            'min_amount' => 500,
            'max_amount' => 5000,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scheme.scheme_type', 'flexible_amount');

        $this->assertDatabaseHas('chit_schemes', [
            'name' => 'Flexible Gold Plan',
            'scheme_type' => 'flexible_amount',
            'min_amount' => 500,
            'max_amount' => 5000,
        ]);
    }

    public function test_admin_can_create_gold_weight_scheme(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('chit-schemes.store'), $this->schemePayload([
            'name' => 'Gold Weight Plan',
            'scheme_type' => 'gold_weight',
            'monthly_amount' => null,
            'gold_weight' => 1.5,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scheme.scheme_type', 'gold_weight');

        $this->assertDatabaseHas('chit_schemes', [
            'name' => 'Gold Weight Plan',
            'scheme_type' => 'gold_weight',
            'gold_weight' => 1.5,
        ]);
    }

    public function test_validation_changes_based_on_scheme_type(): void
    {
        $fixedResponse = $this->actingAs($this->admin())->postJson(route('chit-schemes.store'), $this->schemePayload([
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => null,
        ]));

        $fixedResponse->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.monthly_amount.0', 'Monthly amount is required for fixed amount schemes.');

        $flexibleResponse = $this->actingAs($this->admin())->postJson(route('chit-schemes.store'), $this->schemePayload([
            'scheme_type' => 'flexible_amount',
            'monthly_amount' => null,
            'min_amount' => 5000,
            'max_amount' => 500,
        ]));

        $flexibleResponse->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.max_amount.0', 'Maximum amount must be greater than minimum amount.');
    }

    public function test_scheme_update_works(): void
    {
        $scheme = $this->createScheme([
            'name' => 'Old Scheme',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
        ]);

        $response = $this->actingAs($this->admin())->putJson(route('chit-schemes.update', $scheme), $this->schemePayload([
            'name' => 'Updated Scheme',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1500,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scheme.name', 'Updated Scheme');

        $this->assertDatabaseHas('chit_schemes', [
            'id' => $scheme->id,
            'name' => 'Updated Scheme',
            'monthly_amount' => 1500,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'update',
            'auditable_type' => ChitScheme::class,
            'auditable_id' => $scheme->id,
        ]);
    }

    public function test_scheme_status_change_works(): void
    {
        $scheme = $this->createScheme(['status' => 'active']);

        $response = $this->actingAs($this->admin())->patchJson(route('chit-schemes.status', $scheme), [
            'status' => 'inactive',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scheme.status', 'inactive');

        $this->assertDatabaseHas('chit_schemes', [
            'id' => $scheme->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'status_change',
            'auditable_id' => $scheme->id,
        ]);
    }

    public function test_scheme_delete_blocked_if_active_enrollment_exists(): void
    {
        $scheme = $this->createScheme();
        $this->createEnrollment($scheme);

        $response = $this->actingAs($this->admin())->deleteJson(route('chit-schemes.destroy', $scheme));

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.errors.scheme.0', 'Scheme has active enrollments. Mark the scheme inactive instead of deleting.');

        $this->assertFalse($scheme->fresh()->trashed());
    }

    public function test_scheme_datatables_loads_correctly(): void
    {
        $scheme = $this->createScheme([
            'name' => 'DataTable Scheme',
            'scheme_type' => 'fixed_amount',
        ]);

        $response = $this->actingAs($this->admin())->getJson(route('chit-schemes.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.scheme_code', $scheme->scheme_code)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);
    }

    public function test_api_active_scheme_list_works(): void
    {
        $activeScheme = $this->createScheme([
            'name' => 'Active API Scheme',
            'status' => 'active',
        ]);
        $this->createScheme([
            'name' => 'Inactive API Scheme',
            'status' => 'inactive',
        ]);
        Sanctum::actingAs($this->admin());

        $response = $this->getJson('/api/schemes');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Schemes fetched successfully')
            ->assertJsonPath('data.0.scheme_code', $activeScheme->scheme_code)
            ->assertJsonCount(1, 'data');
    }

    public function test_api_scheme_detail_works(): void
    {
        $scheme = $this->createScheme([
            'name' => 'API Detail Scheme',
        ]);
        Sanctum::actingAs($this->admin());

        $response = $this->getJson('/api/schemes/'.$scheme->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Scheme fetched successfully')
            ->assertJsonPath('data.scheme.name', 'API Detail Scheme');
    }

    public function test_api_response_format_is_consistent(): void
    {
        $scheme = $this->createScheme();
        Sanctum::actingAs($this->admin());

        $response = $this->getJson('/api/schemes/'.$scheme->id);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'scheme' => [
                        'id',
                        'scheme_code',
                        'name',
                        'scheme_type',
                        'duration_months',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function schemePayload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Standard Gold Scheme',
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
            'maturity_rule' => 'Eligible after all installments are paid.',
            'early_closing_rule' => 'Early closure requires manager approval.',
            'status' => 'active',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createScheme(array $overrides = []): ChitScheme
    {
        return ChitScheme::create(array_merge([
            'scheme_code' => 'SCH'.str_pad((string) (ChitScheme::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT),
            'name' => 'Existing Scheme',
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

    private function createEnrollment(ChitScheme $scheme): ChitEnrollment
    {
        $customer = Customer::create([
            'customer_code' => 'CUS'.str_pad((string) (Customer::count() + 1), 6, '0', STR_PAD_LEFT),
            'name' => 'Scheme Customer',
            'mobile' => '98888'.str_pad((string) Customer::count(), 5, '0', STR_PAD_LEFT),
            'address' => 'Scheme address',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
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
