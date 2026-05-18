<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitPayment;
use App\Models\ChitReceipt;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceiptGenerationManagementTest extends TestCase
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

    public function test_receipt_auto_generates_after_payment_with_unique_number_and_matching_amount(): void
    {
        $receipt = $this->collectPaymentAndReceipt();

        $this->assertSame('RCPT000001', $receipt->receipt_no);
        $this->assertSame('1000.00', $receipt->amount);
        $this->assertSame($receipt->payment->total_amount, $receipt->amount);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'receipt',
            'auditable_type' => ChitReceipt::class,
            'auditable_id' => $receipt->id,
        ]);

        $secondReceipt = $this->collectPaymentAndReceipt(codeSuffix: 'B');
        $this->assertSame('RCPT000002', $secondReceipt->receipt_no);
    }

    public function test_thermal_a4_and_duplicate_print_views_load_and_increment_print_count(): void
    {
        $receipt = $this->collectPaymentAndReceipt();

        $this->actingAs($this->admin())->get(route('receipts.thermal-print', $receipt))
            ->assertOk()
            ->assertSee($receipt->receipt_no);
        $this->assertSame(1, $receipt->refresh()->print_count);

        $this->actingAs($this->admin())->get(route('receipts.a4-print', $receipt))
            ->assertOk()
            ->assertSee('Payment Receipt');
        $this->assertSame(2, $receipt->refresh()->print_count);

        $this->actingAs($this->admin())->get(route('receipts.duplicate', $receipt))
            ->assertOk()
            ->assertSee('Duplicate Copy');
        $this->assertSame(3, $receipt->refresh()->print_count);
    }

    public function test_pdf_download_stores_receipt_pdf(): void
    {
        Storage::fake('public');
        $receipt = $this->collectPaymentAndReceipt();

        $this->actingAs($this->admin())->get(route('receipts.pdf', $receipt))
            ->assertOk()
            ->assertHeader('content-disposition');

        $receipt->refresh();
        $this->assertSame('receipts/rcpt000001.pdf', $receipt->pdf_path);
        Storage::disk('public')->assertExists($receipt->pdf_path);
    }

    public function test_receipt_cancellation_does_not_cancel_payment_and_blocks_output(): void
    {
        $receipt = $this->collectPaymentAndReceipt();
        $payment = $receipt->payment;

        $this->actingAs($this->admin())->postJson(route('receipts.cancel', $receipt), [
            'cancellation_reason' => 'Receipt printed incorrectly',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.receipt.status', 'cancelled');

        $this->assertSame('success', $payment->refresh()->status);
        $this->assertSame('cancelled', $receipt->refresh()->status);
        $this->assertSame('Receipt printed incorrectly', $receipt->cancellation_reason);

        $this->actingAs($this->admin())->get(route('receipts.thermal-print', $receipt))
            ->assertRedirect(route('receipts.show', $receipt));
    }

    public function test_whatsapp_placeholder_and_datatables_work(): void
    {
        $receipt = $this->collectPaymentAndReceipt();

        $this->actingAs($this->admin())->postJson(route('receipts.whatsapp', $receipt))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.share.status', 'placeholder')
            ->assertJsonPath('data.share.mobile', $receipt->customer->mobile);

        $this->actingAs($this->admin())->getJson(route('receipts.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))->assertOk()
            ->assertJsonPath('data.0.receipt_no', $receipt->receipt_no)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);
    }

    public function test_api_receipt_list_view_download_and_response_format_work(): void
    {
        Storage::fake('public');
        $receipt = $this->collectPaymentAndReceipt();
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/receipts')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Receipts fetched successfully')
            ->assertJsonPath('data.0.receipt_no', $receipt->receipt_no);

        $this->getJson('/api/receipts/'.$receipt->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Receipt fetched successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'receipt' => [
                        'id',
                        'receipt_no',
                        'amount',
                        'payment',
                    ],
                ],
            ]);

        $this->get('/api/receipts/'.$receipt->id.'/download')
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    private function collectPaymentAndReceipt(string $codeSuffix = 'A'): ChitReceipt
    {
        $enrollment = $this->createEnrollmentWithInstallments(codeSuffix: $codeSuffix);

        $this->actingAs($this->admin())->postJson(route('payments.store'), $this->paymentPayload($enrollment, [
            'payment_type' => 'full',
            'installment_id' => $enrollment->installments()->firstOrFail()->id,
            'amount' => 1000,
        ]))->assertOk();

        return ChitReceipt::query()
            ->with(['payment', 'customer', 'enrollment.scheme'])
            ->latest('id')
            ->firstOrFail();
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'customer_code' => 'RCPTCUS'.str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
            'name' => 'Receipt Customer',
            'mobile' => '904000'.str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
            'address' => 'Receipt address',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
            'status' => 'active',
        ]);
    }

    private function createEnrollmentWithInstallments(string $codeSuffix = 'A'): ChitEnrollment
    {
        $customer = $this->createCustomer();
        $scheme = ChitScheme::create([
            'scheme_code' => 'RCPTS'.$codeSuffix.ChitScheme::count(),
            'name' => 'Receipt Scheme '.$codeSuffix,
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'duration_months' => 1,
            'shop_bonus_type' => 'none',
            'shop_bonus_value' => 0,
            'grace_period_days' => 0,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'status' => 'active',
        ]);

        $enrollment = ChitEnrollment::create([
            'chit_no' => 'RCPTCHIT'.str_pad((string) (ChitEnrollment::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'scheme_id' => $scheme->id,
            'branch_id' => Branch::where('branch_code', 'MAIN')->value('id'),
            'assigned_staff_id' => User::where('email', 'staff@example.com')->value('id'),
            'start_date' => '2026-05-19',
            'monthly_due_date' => 19,
            'maturity_date' => '2026-06-19',
            'total_months' => 1,
            'monthly_amount' => 1000,
            'total_payable' => 1000,
            'total_paid' => 0,
            'total_pending' => 1000,
            'status' => 'active',
            'created_by' => $this->admin()->id,
        ]);

        ChitInstallment::create([
            'enrollment_id' => $enrollment->id,
            'installment_no' => 1,
            'due_date' => '2026-05-19',
            'due_amount' => 1000,
            'paid_amount' => 0,
            'balance_amount' => 1000,
            'late_fee' => 0,
            'status' => 'pending',
        ]);

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
            'remarks' => 'Receipt test',
            'payment_type' => 'partial',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }
}
