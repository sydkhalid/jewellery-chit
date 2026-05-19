<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cashbook;
use App\Models\ChitClosure;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitLedger;
use App\Models\ChitPayment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\GoldRate;
use App\Models\JewelleryInvoice;
use App\Models\PaymentMode;
use App\Models\User;
use App\Services\CashflowService;
use App\Services\JewelleryInvoiceService;
use App\Services\LedgerService;
use App\Services\MaturityClosingService;
use App\Services\PendingDueService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseOneFullSystemQaTest extends TestCase
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

    public function test_api_authentication_sanctum_protection_and_role_restrictions(): void
    {
        $this->getJson('/api/customers')->assertUnauthorized();

        $login = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'admin@example.com');

        Sanctum::actingAs($this->staff());
        $this->getJson('/api/reports/dashboard-summary')->assertForbidden();

        Sanctum::actingAs($this->admin());
        $this->getJson('/api/reports/dashboard-summary')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['customers', 'active_chits', 'collections', 'pending']]);
    }

    public function test_ledger_pending_due_reminders_cashbook_and_reports_are_connected(): void
    {
        $this->actingAs($this->admin());

        $enrollment = $this->createEnrollmentWithInstallments(
            status: 'active',
            firstDueDate: '2026-04-19',
            months: 2
        );
        $installment = $enrollment->installments()->firstOrFail();

        $ledgerService = app(LedgerService::class);
        $dueEntry = $ledgerService->createDueEntry($installment);
        $payment = $this->createPayment($enrollment, 500);
        $paymentEntry = $ledgerService->createPaymentEntry($payment);

        $this->assertSame('1000.00', $dueEntry->debit);
        $this->assertSame('500.00', $paymentEntry->credit);
        $this->assertSame('500.00', $ledgerService->calculateRunningBalance($enrollment)->last()->balance);

        $pendingDueService = app(PendingDueService::class);
        $this->assertTrue($pendingDueService->getOverdueDues()->contains('id', $installment->id));

        $pendingDueService->updateFollowUpStatus($installment, [
            'followup_status' => 'promised',
            'promise_to_pay_date' => '2026-05-20',
            'remarks' => 'Will pay tomorrow',
        ]);
        $pendingDueService->sendDueReminder($installment->refresh(), 'sms');

        $this->assertDatabaseHas('chit_installments', [
            'id' => $installment->id,
            'followup_status' => 'promised',
            'reminder_count' => 1,
        ]);
        $this->assertDatabaseHas('sms_logs', [
            'customer_id' => $enrollment->customer_id,
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('notifications', [
            'customer_id' => $enrollment->customer_id,
            'notification_type' => 'due_reminder',
            'channel' => 'sms',
        ]);

        $cashflow = app(CashflowService::class);
        $cashflow->createOpeningBalance([
            'branch_id' => $enrollment->branch_id,
            'cashbook_date' => '2026-05-19',
            'credit' => 1000,
            'remarks' => 'QA opening',
        ]);
        $cashflow->createCashbookEntry([
            'branch_id' => $enrollment->branch_id,
            'cashbook_date' => '2026-05-19',
            'transaction_type' => 'cash_received',
            'payment_mode_id' => $this->cashModeId(),
            'credit' => 250,
            'remarks' => 'QA collection',
        ]);
        $cashflow->createCashbookEntry([
            'branch_id' => $enrollment->branch_id,
            'cashbook_date' => '2026-05-19',
            'transaction_type' => 'refund',
            'debit' => 100,
            'remarks' => 'QA refund',
        ]);

        $summary = $cashflow->calculateDailyCashflow('2026-05-19', $enrollment->branch_id);
        $this->assertSame(100.0, $summary['debit_total']);
        $this->assertSame(1250.0, $summary['credit_total']);
        $this->assertSame(1150.0, $summary['closing_balance']);

        $this->actingAs($this->admin())->get(route('reports.customers', ['summary' => 1]))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->actingAs($this->admin())->get(route('reports.excel', 'customers'))->assertOk();
        $this->actingAs($this->admin())->get(route('reports.pdf', 'customers'))->assertOk();
    }

    public function test_maturity_closing_refund_and_jewellery_invoice_adjustment_flow(): void
    {
        $this->actingAs($this->admin());

        $enrollment = $this->createEnrollmentWithInstallments(
            status: 'active',
            firstDueDate: '2026-04-19',
            months: 1,
            maturityDate: '2026-05-18'
        );
        $enrollment->installments()->update([
            'paid_amount' => 1000,
            'balance_amount' => 0,
            'status' => 'paid',
        ]);
        $this->createPayment($enrollment, 1000);
        $enrollment->update([
            'total_paid' => 1000,
            'total_pending' => 0,
        ]);

        $closingService = app(MaturityClosingService::class);
        $closure = $closingService->createNormalClosing($enrollment->refresh(), [
            'deductions' => 0,
            'refund_amount' => 200,
            'jewellery_adjustment_amount' => 0,
            'remarks' => 'QA maturity closing',
        ]);
        $closure = $closingService->approveClosing($closure);
        $closure = $closingService->completeClosing($closure);

        $this->assertSame('completed', $closure->status);
        $this->assertSame('closed', $enrollment->refresh()->status);
        $this->assertDatabaseHas('chit_refunds', [
            'enrollment_id' => $enrollment->id,
            'amount' => 200,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('cashbooks', [
            'transaction_type' => 'refund',
            'debit' => 200,
        ]);

        GoldRate::create([
            'rate_date' => '2026-05-19',
            'gold_22k' => 5800,
            'gold_24k' => 6300,
            'silver_rate' => 90,
            'status' => 'approved',
            'approved_by' => $this->admin()->id,
            'approved_at' => now(),
            'rate_locked' => false,
            'created_by' => $this->admin()->id,
        ]);

        $invoice = app(JewelleryInvoiceService::class)->createInvoice([
            'customer_id' => $enrollment->customer_id,
            'enrollment_id' => $enrollment->id,
            'invoice_date' => '2026-05-19',
            'gold_rate' => 5800,
            'discount' => 50,
            'chit_adjustment_amount' => 500,
            'items' => [
                [
                    'item_name' => 'Gold Chain',
                    'purity' => '22K',
                    'gross_weight' => 1.200,
                    'net_weight' => 1.000,
                    'rate' => 5800,
                    'making_charge' => 200,
                    'wastage' => 50,
                    'gst_amount' => 100,
                ],
            ],
        ]);

        $this->assertSame('6100.00', $invoice->total_amount);
        $this->assertSame('5600.00', $invoice->balance_payable);

        $invoice = app(JewelleryInvoiceService::class)->finalizeInvoice($invoice);
        $this->assertSame('final', $invoice->status);
        $this->assertDatabaseHas('chit_ledgers', [
            'reference_type' => JewelleryInvoice::class,
            'reference_id' => $invoice->id,
            'transaction_type' => 'adjustment',
            'credit' => 500,
        ]);
        $this->assertDatabaseHas('cashbooks', [
            'reference_type' => JewelleryInvoice::class,
            'reference_id' => $invoice->id,
            'transaction_type' => 'jewellery_adjustment',
            'debit' => 500,
        ]);
    }

    public function test_api_lists_use_standard_envelope_and_pagination_meta(): void
    {
        $customer = $this->createCustomer();
        $scheme = $this->createScheme();
        $enrollment = $this->createEnrollmentWithInstallments(customer: $customer, scheme: $scheme);

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

        $this->getJson('/api/schemes')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/chit-enrollments/'.$enrollment->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['enrollment' => ['id', 'customer', 'scheme']]]);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_replace([
            'customer_code' => 'QACUS'.str_pad((string) (Customer::count() + 1), 5, '0', STR_PAD_LEFT),
            'name' => 'QA Customer',
            'mobile' => '955000'.str_pad((string) (Customer::count() + 1), 4, '0', STR_PAD_LEFT),
            'address' => 'QA Street',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'pincode' => '600001',
            'status' => 'active',
            'created_by' => $this->admin()->id,
        ], $overrides));
    }

    private function createScheme(array $overrides = []): ChitScheme
    {
        return ChitScheme::create(array_replace([
            'scheme_code' => 'QASCH'.str_pad((string) (ChitScheme::count() + 1), 5, '0', STR_PAD_LEFT),
            'name' => 'QA Fixed Scheme',
            'scheme_type' => 'fixed_amount',
            'monthly_amount' => 1000,
            'duration_months' => 2,
            'shop_bonus_type' => 'none',
            'shop_bonus_value' => 0,
            'grace_period_days' => 0,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'status' => 'active',
        ], $overrides));
    }

    private function createEnrollmentWithInstallments(
        ?Customer $customer = null,
        ?ChitScheme $scheme = null,
        string $status = 'active',
        string $firstDueDate = '2026-05-19',
        int $months = 2,
        ?string $maturityDate = null
    ): ChitEnrollment {
        $customer ??= $this->createCustomer();
        $scheme ??= $this->createScheme(['duration_months' => $months]);
        $start = Carbon::parse($firstDueDate);

        $enrollment = ChitEnrollment::create([
            'chit_no' => 'QACHIT'.str_pad((string) (ChitEnrollment::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'scheme_id' => $scheme->id,
            'branch_id' => Branch::where('branch_code', 'MAIN')->value('id'),
            'assigned_staff_id' => $this->staff()->id,
            'start_date' => $firstDueDate,
            'monthly_due_date' => (int) $start->format('d'),
            'maturity_date' => $maturityDate ?? $start->copy()->addMonthsNoOverflow($months)->toDateString(),
            'total_months' => $months,
            'monthly_amount' => 1000,
            'total_payable' => $months * 1000,
            'total_paid' => 0,
            'total_pending' => $months * 1000,
            'status' => $status,
            'created_by' => $this->admin()->id,
        ]);

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

    private function createPayment(ChitEnrollment $enrollment, float $amount): ChitPayment
    {
        return ChitPayment::create([
            'payment_no' => 'QAPAY'.str_pad((string) (ChitPayment::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT),
            'enrollment_id' => $enrollment->id,
            'customer_id' => $enrollment->customer_id,
            'installment_id' => $enrollment->installments()->first()?->id,
            'payment_mode_id' => $this->cashModeId(),
            'branch_id' => $enrollment->branch_id,
            'staff_id' => $enrollment->assigned_staff_id,
            'payment_date' => '2026-05-19',
            'amount' => $amount,
            'late_fee_amount' => 0,
            'total_amount' => $amount,
            'payment_type' => 'partial',
            'status' => 'success',
            'created_by' => $this->admin()->id,
        ]);
    }

    private function cashModeId(): int
    {
        return (int) PaymentMode::where('code', 'cash')->value('id');
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function staff(): User
    {
        return User::where('email', 'staff@example.com')->firstOrFail();
    }
}
