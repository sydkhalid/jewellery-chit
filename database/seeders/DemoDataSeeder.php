<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\Branch;
use App\Models\Cashbook;
use App\Models\ChitCancellation;
use App\Models\ChitClosure;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitLedger;
use App\Models\ChitPayment;
use App\Models\ChitPaymentAllocation;
use App\Models\ChitReceipt;
use App\Models\ChitRefund;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\GoldRate;
use App\Models\JewelleryInvoice;
use App\Models\JewelleryInvoiceItem;
use App\Models\Nominee;
use App\Models\Notification;
use App\Models\PaymentMode;
use App\Models\SmsLog;
use App\Models\StaffCashHandover;
use App\Models\User;
use App\Models\WhatsappLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                RolePermissionSeeder::class,
                BranchSeeder::class,
                PaymentModeSeeder::class,
                ShopSettingSeeder::class,
                DefaultUserSeeder::class,
            ]);

            $admin = User::where('email', 'admin@example.com')->firstOrFail();
            Auth::setUser($admin);

            $branches = $this->seedBranches();
            $users = $this->seedUsers($branches);
            $paymentModes = PaymentMode::query()->get()->keyBy('code');
            $schemes = $this->seedSchemes($admin);
            $customers = $this->seedCustomers($admin);
            $this->seedGoldRates($admin);

            $enrollments = $this->seedEnrollments($customers, $schemes, $branches, $users, $admin);
            $installments = $this->seedInstallments($enrollments);
            $payments = $this->seedPayments($enrollments, $installments, $paymentModes, $branches, $users, $admin);
            $this->seedReceipts($payments);
            $this->seedLedger($enrollments, $installments, $payments, $admin);
            $this->seedClosingsRefundsAndCancellations($enrollments, $paymentModes, $admin);
            $this->seedJewelleryInvoices($enrollments, $admin);
            $this->seedCashbook($payments, $paymentModes, $branches, $users, $admin);
            $this->seedMessages($customers, $enrollments, $installments);
            $this->seedOperationalLogs($admin, $customers, $schemes, $enrollments);
        });
    }

    /**
     * @return array<string, Branch>
     */
    private function seedBranches(): array
    {
        $records = [
            'MAIN' => ['name' => 'Main Branch', 'mobile' => '04440001000', 'email' => 'main@example.com', 'address' => '12 Gold Street', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600001'],
            'TNGR' => ['name' => 'T Nagar Branch', 'mobile' => '04440002000', 'email' => 'tnagar@example.com', 'address' => '45 Usman Road', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600017'],
            'VLCY' => ['name' => 'Velachery Branch', 'mobile' => '04440003000', 'email' => 'velachery@example.com', 'address' => '88 Main Road', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600042'],
        ];

        $branches = [];

        foreach ($records as $code => $data) {
            $branches[$code] = Branch::updateOrCreate(
                ['branch_code' => $code],
                $data + ['status' => 'active']
            );
        }

        return $branches;
    }

    /**
     * @param  array<string, Branch>  $branches
     * @return array<string, User>
     */
    private function seedUsers(array $branches): array
    {
        $users = [
            'admin' => User::where('email', 'admin@example.com')->firstOrFail(),
            'manager' => User::where('email', 'manager@example.com')->firstOrFail(),
            'staff' => User::where('email', 'staff@example.com')->firstOrFail(),
        ];

        $users['collector'] = User::updateOrCreate(
            ['email' => 'collector@example.com'],
            [
                'name' => 'Demo Collector',
                'mobile' => '6666666666',
                'password' => Hash::make('password'),
                'branch_id' => $branches['TNGR']->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $users['collector']->syncRoles(['Staff']);

        return $users;
    }

    /**
     * @return array<string, ChitScheme>
     */
    private function seedSchemes(User $admin): array
    {
        $records = [
            'fixed' => [
                'scheme_code' => 'SCH-DEMO-FIXED',
                'name' => 'Demo Fixed Monthly 3000',
                'scheme_type' => 'fixed_amount',
                'monthly_amount' => 3000,
                'min_amount' => null,
                'max_amount' => null,
                'gold_weight' => null,
                'duration_months' => 6,
                'shop_bonus_type' => 'fixed',
                'shop_bonus_value' => 500,
                'grace_period_days' => 5,
                'late_fee_type' => 'fixed',
                'late_fee_value' => 50,
                'maturity_rule' => 'Full benefit after all installments are paid.',
                'early_closing_rule' => 'Early closing deducts current month bonus.',
                'status' => 'active',
            ],
            'flexible' => [
                'scheme_code' => 'SCH-DEMO-FLEX',
                'name' => 'Demo Flexible Savings',
                'scheme_type' => 'flexible_amount',
                'monthly_amount' => null,
                'min_amount' => 1000,
                'max_amount' => 10000,
                'gold_weight' => null,
                'duration_months' => 10,
                'shop_bonus_type' => 'percentage',
                'shop_bonus_value' => 5,
                'grace_period_days' => 7,
                'late_fee_type' => 'percentage',
                'late_fee_value' => 2,
                'maturity_rule' => 'Bonus calculated on total paid amount.',
                'early_closing_rule' => 'No bonus before six paid months.',
                'status' => 'active',
            ],
            'gold' => [
                'scheme_code' => 'SCH-DEMO-GOLD',
                'name' => 'Demo Gold Weight 1g',
                'scheme_type' => 'gold_weight',
                'monthly_amount' => null,
                'min_amount' => null,
                'max_amount' => null,
                'gold_weight' => 1,
                'duration_months' => 8,
                'shop_bonus_type' => 'none',
                'shop_bonus_value' => 0,
                'grace_period_days' => 5,
                'late_fee_type' => 'none',
                'late_fee_value' => 0,
                'maturity_rule' => 'Gold weight benefit calculated using approved rate.',
                'early_closing_rule' => 'Gold weight benefit not applicable before maturity.',
                'status' => 'active',
            ],
            'inactive' => [
                'scheme_code' => 'SCH-DEMO-INACTIVE',
                'name' => 'Demo Old Scheme',
                'scheme_type' => 'fixed_amount',
                'monthly_amount' => 1500,
                'min_amount' => null,
                'max_amount' => null,
                'gold_weight' => null,
                'duration_months' => 5,
                'shop_bonus_type' => 'none',
                'shop_bonus_value' => 0,
                'grace_period_days' => 0,
                'late_fee_type' => 'none',
                'late_fee_value' => 0,
                'maturity_rule' => 'Legacy plan for reports.',
                'early_closing_rule' => 'Legacy plan closed.',
                'status' => 'inactive',
            ],
        ];

        $schemes = [];

        foreach ($records as $key => $data) {
            $schemes[$key] = ChitScheme::updateOrCreate(
                ['scheme_code' => $data['scheme_code']],
                $data + ['created_by' => $admin->id, 'updated_by' => $admin->id]
            );
        }

        return $schemes;
    }

    /**
     * @return array<string, Customer>
     */
    private function seedCustomers(User $admin): array
    {
        $records = [
            'lakshmi' => ['customer_code' => 'CUS-DEMO-001', 'name' => 'Lakshmi Narayanan', 'mobile' => '9000000001', 'alternate_mobile' => '9000000011', 'email' => 'lakshmi@example.com', 'aadhaar_no' => '111122223333', 'pan_no' => 'ABCDE1234F', 'address' => '15 Temple Street', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600004'],
            'kumar' => ['customer_code' => 'CUS-DEMO-002', 'name' => 'Kumar Raj', 'mobile' => '9000000002', 'alternate_mobile' => null, 'email' => 'kumar@example.com', 'aadhaar_no' => null, 'pan_no' => 'BCDEF2345G', 'address' => '24 Lake View Road', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600042'],
            'priya' => ['customer_code' => 'CUS-DEMO-003', 'name' => 'Priya S', 'mobile' => '9000000003', 'alternate_mobile' => '9000000013', 'email' => 'priya@example.com', 'aadhaar_no' => '222233334444', 'pan_no' => null, 'address' => '8 Garden Avenue', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600020'],
            'arun' => ['customer_code' => 'CUS-DEMO-004', 'name' => 'Arun Kumar', 'mobile' => '9000000004', 'alternate_mobile' => null, 'email' => 'arun@example.com', 'aadhaar_no' => '333344445555', 'pan_no' => 'CDEFG3456H', 'address' => '31 Bazaar Road', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600017'],
            'meena' => ['customer_code' => 'CUS-DEMO-005', 'name' => 'Meena R', 'mobile' => '9000000005', 'alternate_mobile' => '9000000015', 'email' => 'meena@example.com', 'aadhaar_no' => null, 'pan_no' => null, 'address' => '72 Station Road', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600001'],
        ];

        $customers = [];

        foreach ($records as $key => $data) {
            $customers[$key] = Customer::updateOrCreate(
                ['customer_code' => $data['customer_code']],
                $data + ['photo' => null, 'status' => 'active', 'created_by' => $admin->id, 'updated_by' => $admin->id]
            );

            Nominee::updateOrCreate(
                ['customer_id' => $customers[$key]->id],
                [
                    'name' => $data['name'].' Nominee',
                    'relationship' => 'Family',
                    'mobile' => $data['alternate_mobile'] ?: null,
                    'address' => $data['address'],
                    'aadhaar_no' => null,
                ]
            );

            CustomerDocument::updateOrCreate(
                ['customer_id' => $customers[$key]->id, 'document_type' => 'aadhaar'],
                [
                    'document_number' => $data['aadhaar_no'],
                    'file_path' => 'demo-documents/'.$data['customer_code'].'-aadhaar.pdf',
                    'status' => 'active',
                    'uploaded_by' => $admin->id,
                ]
            );
        }

        return $customers;
    }

    private function seedGoldRates(User $admin): void
    {
        foreach ([0 => [6150, 6700, 80], 1 => [6125, 6675, 79], 2 => [6100, 6650, 78]] as $daysAgo => $rates) {
            GoldRate::updateOrCreate(
                ['rate_date' => today()->subDays($daysAgo)->toDateString()],
                [
                    'gold_22k' => $rates[0],
                    'gold_24k' => $rates[1],
                    'silver_rate' => $rates[2],
                    'status' => $daysAgo === 0 ? 'approved' : 'pending',
                    'approved_by' => $daysAgo === 0 ? $admin->id : null,
                    'approved_at' => $daysAgo === 0 ? now() : null,
                    'rate_locked' => $daysAgo === 0,
                    'created_by' => $admin->id,
                ]
            );
        }
    }

    /**
     * @param  array<string, Customer>  $customers
     * @param  array<string, ChitScheme>  $schemes
     * @param  array<string, Branch>  $branches
     * @param  array<string, User>  $users
     * @return array<string, ChitEnrollment>
     */
    private function seedEnrollments(array $customers, array $schemes, array $branches, array $users, User $admin): array
    {
        $records = [
            'active_paid' => ['chit_no' => 'CHIT-DEMO-001', 'customer' => 'lakshmi', 'scheme' => 'fixed', 'branch' => 'MAIN', 'staff' => 'staff', 'start' => today()->subMonths(2), 'monthly_amount' => 3000, 'status' => 'active'],
            'active_overdue' => ['chit_no' => 'CHIT-DEMO-002', 'customer' => 'kumar', 'scheme' => 'flexible', 'branch' => 'TNGR', 'staff' => 'collector', 'start' => today()->subMonths(3), 'monthly_amount' => 2500, 'status' => 'active'],
            'matured' => ['chit_no' => 'CHIT-DEMO-003', 'customer' => 'priya', 'scheme' => 'fixed', 'branch' => 'VLCY', 'staff' => 'staff', 'start' => today()->subMonths(7), 'monthly_amount' => 3000, 'status' => 'matured'],
            'closed' => ['chit_no' => 'CHIT-DEMO-004', 'customer' => 'arun', 'scheme' => 'fixed', 'branch' => 'MAIN', 'staff' => 'manager', 'start' => today()->subMonths(8), 'monthly_amount' => 3000, 'status' => 'closed'],
            'cancelled' => ['chit_no' => 'CHIT-DEMO-005', 'customer' => 'meena', 'scheme' => 'gold', 'branch' => 'TNGR', 'staff' => 'collector', 'start' => today()->subMonths(1), 'monthly_amount' => 0, 'status' => 'cancelled'],
        ];

        $enrollments = [];

        foreach ($records as $key => $data) {
            $scheme = $schemes[$data['scheme']];
            $totalPayable = (float) $data['monthly_amount'] * (int) $scheme->duration_months;
            $start = CarbonImmutable::parse($data['start']);

            $enrollments[$key] = ChitEnrollment::updateOrCreate(
                ['chit_no' => $data['chit_no']],
                [
                    'customer_id' => $customers[$data['customer']]->id,
                    'scheme_id' => $scheme->id,
                    'branch_id' => $branches[$data['branch']]->id,
                    'assigned_staff_id' => $users[$data['staff']]->id,
                    'start_date' => $start->toDateString(),
                    'monthly_due_date' => (int) $start->day,
                    'maturity_date' => $start->addMonths((int) $scheme->duration_months)->toDateString(),
                    'agreement_file' => 'demo-agreements/'.$data['chit_no'].'.pdf',
                    'remarks' => 'Demo enrollment',
                    'total_months' => $scheme->duration_months,
                    'monthly_amount' => $data['monthly_amount'],
                    'total_payable' => $totalPayable,
                    'total_paid' => 0,
                    'total_pending' => $totalPayable,
                    'status' => $data['status'],
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
        }

        return $enrollments;
    }

    /**
     * @param  array<string, ChitEnrollment>  $enrollments
     * @return array<string, array<int, ChitInstallment>>
     */
    private function seedInstallments(array $enrollments): array
    {
        $installments = [];

        foreach ($enrollments as $key => $enrollment) {
            $installments[$key] = [];
            $start = CarbonImmutable::parse($enrollment->start_date);
            $monthlyAmount = (float) $enrollment->monthly_amount;

            for ($month = 1; $month <= (int) $enrollment->total_months; $month++) {
                $dueDate = $start->addMonths($month - 1);
                $paidAmount = 0.0;
                $lateFee = 0.0;
                $status = $dueDate->isPast() ? 'overdue' : 'pending';
                $paidDate = null;

                if (in_array($key, ['matured', 'closed'], true)) {
                    $paidAmount = $monthlyAmount;
                    $status = 'paid';
                    $paidDate = $dueDate->addDays(2)->toDateString();
                } elseif ($key === 'active_paid' && $month <= 2) {
                    $paidAmount = $monthlyAmount;
                    $status = 'paid';
                    $paidDate = $dueDate->addDays(2)->toDateString();
                } elseif ($key === 'active_overdue' && $month === 1) {
                    $paidAmount = 1000;
                    $lateFee = 50;
                    $status = 'partial';
                    $paidDate = $dueDate->addDays(6)->toDateString();
                } elseif ($key === 'cancelled') {
                    $status = 'pending';
                }

                $installment = ChitInstallment::updateOrCreate(
                    ['enrollment_id' => $enrollment->id, 'installment_no' => $month],
                    [
                        'due_date' => $dueDate->toDateString(),
                        'due_amount' => $monthlyAmount,
                        'paid_amount' => $paidAmount,
                        'balance_amount' => max(0, $monthlyAmount - $paidAmount),
                        'late_fee' => $lateFee,
                        'status' => $status,
                        'paid_date' => $paidDate,
                        'followup_status' => $status === 'overdue' || $status === 'partial' ? 'called' : 'pending',
                        'promise_to_pay_date' => $status === 'overdue' || $status === 'partial' ? today()->addDays(3)->toDateString() : null,
                        'followup_remarks' => $status === 'overdue' || $status === 'partial' ? 'Demo follow-up scheduled.' : null,
                        'last_followup_at' => $status === 'overdue' || $status === 'partial' ? now() : null,
                        'reminder_count' => $status === 'overdue' || $status === 'partial' ? 1 : 0,
                        'last_reminder_at' => $status === 'overdue' || $status === 'partial' ? now() : null,
                    ]
                );

                $installments[$key][$month] = $installment;
            }

            $this->refreshEnrollmentTotals($enrollment);
        }

        return $installments;
    }

    /**
     * @param  array<string, ChitEnrollment>  $enrollments
     * @param  array<string, array<int, ChitInstallment>>  $installments
     * @param  \Illuminate\Support\Collection<string, PaymentMode>  $paymentModes
     * @param  array<string, Branch>  $branches
     * @param  array<string, User>  $users
     * @return array<string, ChitPayment>
     */
    private function seedPayments(array $enrollments, array $installments, $paymentModes, array $branches, array $users, User $admin): array
    {
        $records = [
            'PAY-DEMO-001' => ['enrollment' => 'active_paid', 'installment' => 1, 'mode' => 'cash', 'amount' => 3000, 'late_fee' => 0, 'type' => 'full', 'date' => today()->subMonths(2)->addDays(2), 'branch' => 'MAIN', 'staff' => 'staff'],
            'PAY-DEMO-002' => ['enrollment' => 'active_paid', 'installment' => 2, 'mode' => 'upi', 'amount' => 3000, 'late_fee' => 0, 'type' => 'full', 'date' => today()->subMonth()->addDays(2), 'branch' => 'MAIN', 'staff' => 'staff'],
            'PAY-DEMO-003' => ['enrollment' => 'active_overdue', 'installment' => 1, 'mode' => 'cash', 'amount' => 1000, 'late_fee' => 50, 'type' => 'partial', 'date' => today()->subMonths(3)->addDays(6), 'branch' => 'TNGR', 'staff' => 'collector'],
            'PAY-DEMO-004' => ['enrollment' => 'matured', 'installment' => 6, 'mode' => 'bank_transfer', 'amount' => 18000, 'late_fee' => 0, 'type' => 'multiple_month', 'date' => today()->subMonth(), 'branch' => 'VLCY', 'staff' => 'staff'],
            'PAY-DEMO-005' => ['enrollment' => 'closed', 'installment' => 6, 'mode' => 'card', 'amount' => 18000, 'late_fee' => 0, 'type' => 'multiple_month', 'date' => today()->subWeeks(3), 'branch' => 'MAIN', 'staff' => 'manager'],
        ];

        $payments = [];

        foreach ($records as $paymentNo => $data) {
            $enrollment = $enrollments[$data['enrollment']];
            $installment = $installments[$data['enrollment']][$data['installment']] ?? null;
            $mode = $paymentModes[$data['mode']];

            $payments[$paymentNo] = ChitPayment::updateOrCreate(
                ['payment_no' => $paymentNo],
                [
                    'enrollment_id' => $enrollment->id,
                    'customer_id' => $enrollment->customer_id,
                    'installment_id' => $installment?->id,
                    'payment_mode_id' => $mode->id,
                    'branch_id' => $branches[$data['branch']]->id,
                    'staff_id' => $users[$data['staff']]->id,
                    'payment_date' => CarbonImmutable::parse($data['date'])->toDateString(),
                    'amount' => $data['amount'],
                    'late_fee_amount' => $data['late_fee'],
                    'total_amount' => $data['amount'] + $data['late_fee'],
                    'transaction_id' => $data['mode'] === 'cash' ? null : 'TXN-'.$paymentNo,
                    'remarks' => 'Demo payment',
                    'payment_type' => $data['type'],
                    'status' => 'success',
                    'edit_status' => 'none',
                    'created_by' => $admin->id,
                ]
            );

            if ($installment) {
                ChitPaymentAllocation::updateOrCreate(
                    ['payment_id' => $payments[$paymentNo]->id, 'installment_id' => $installment->id],
                    ['amount' => $data['amount'], 'late_fee_amount' => $data['late_fee']]
                );
            }
        }

        foreach ($enrollments as $enrollment) {
            $this->refreshEnrollmentTotals($enrollment);
        }

        return $payments;
    }

    /**
     * @param  array<string, ChitPayment>  $payments
     */
    private function seedReceipts(array $payments): void
    {
        $index = 1;

        foreach ($payments as $payment) {
            ChitReceipt::updateOrCreate(
                ['receipt_no' => 'RCPT-DEMO-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT)],
                [
                    'payment_id' => $payment->id,
                    'enrollment_id' => $payment->enrollment_id,
                    'customer_id' => $payment->customer_id,
                    'receipt_date' => $payment->payment_date,
                    'amount' => $payment->total_amount,
                    'pdf_path' => 'receipts/demo-'.$payment->payment_no.'.pdf',
                    'print_count' => $index === 1 ? 1 : 0,
                    'status' => 'active',
                ]
            );

            $index++;
        }
    }

    /**
     * @param  array<string, ChitEnrollment>  $enrollments
     * @param  array<string, array<int, ChitInstallment>>  $installments
     * @param  array<string, ChitPayment>  $payments
     */
    private function seedLedger(array $enrollments, array $installments, array $payments, User $admin): void
    {
        foreach ($installments as $items) {
            foreach ($items as $installment) {
                $enrollment = $installment->enrollment;

                ChitLedger::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'transaction_type' => 'due',
                        'reference_type' => ChitInstallment::class,
                        'reference_id' => $installment->id,
                    ],
                    [
                        'customer_id' => $enrollment->customer_id,
                        'transaction_date' => $installment->due_date,
                        'debit' => $installment->due_amount,
                        'credit' => 0,
                        'balance' => $installment->balance_amount,
                        'remarks' => 'Demo monthly due',
                        'created_by' => $admin->id,
                    ]
                );
            }
        }

        foreach ($payments as $payment) {
            ChitLedger::updateOrCreate(
                [
                    'enrollment_id' => $payment->enrollment_id,
                    'transaction_type' => $payment->payment_type === 'advance' ? 'advance' : 'payment',
                    'reference_type' => ChitPayment::class,
                    'reference_id' => $payment->id,
                ],
                [
                    'customer_id' => $payment->customer_id,
                    'transaction_date' => $payment->payment_date,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'balance' => $payment->enrollment?->total_pending ?? 0,
                    'remarks' => 'Demo payment ledger entry',
                    'created_by' => $admin->id,
                ]
            );
        }
    }

    /**
     * @param  array<string, ChitEnrollment>  $enrollments
     * @param  \Illuminate\Support\Collection<string, PaymentMode>  $paymentModes
     */
    private function seedClosingsRefundsAndCancellations(array $enrollments, $paymentModes, User $admin): void
    {
        $closed = $enrollments['closed']->refresh();

        $closure = ChitClosure::updateOrCreate(
            ['closure_no' => 'CLS-DEMO-001'],
            [
                'enrollment_id' => $closed->id,
                'customer_id' => $closed->customer_id,
                'closure_type' => 'normal',
                'total_paid' => 18000,
                'shop_bonus' => 500,
                'deductions' => 0,
                'final_maturity_value' => 18500,
                'refund_amount' => 5000,
                'jewellery_adjustment_amount' => 13500,
                'customer_signature' => 'demo-signatures/CLS-DEMO-001.png',
                'remarks' => 'Demo completed maturity closing',
                'status' => 'completed',
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(12),
                'completed_by' => $admin->id,
                'completed_at' => now()->subDays(10),
                'created_by' => $admin->id,
            ]
        );

        ChitRefund::updateOrCreate(
            ['refund_no' => 'RFND-DEMO-001'],
            [
                'enrollment_id' => $closed->id,
                'customer_id' => $closed->customer_id,
                'payment_mode_id' => $paymentModes['bank_transfer']->id,
                'refund_date' => today()->subDays(10)->toDateString(),
                'amount' => 5000,
                'transaction_id' => 'REF-DEMO-001',
                'remarks' => 'Demo closing refund',
                'status' => 'paid',
                'created_by' => $admin->id,
            ]
        );

        ChitCancellation::updateOrCreate(
            ['enrollment_id' => $enrollments['cancelled']->id],
            [
                'customer_id' => $enrollments['cancelled']->customer_id,
                'cancellation_date' => today()->subDays(3)->toDateString(),
                'reason' => 'Demo cancellation before first payment',
                'refund_amount' => 0,
                'deduction_amount' => 0,
                'cancelled_by' => $admin->id,
            ]
        );

        ChitLedger::updateOrCreate(
            ['transaction_type' => 'closing', 'reference_type' => ChitClosure::class, 'reference_id' => $closure->id],
            [
                'enrollment_id' => $closed->id,
                'customer_id' => $closed->customer_id,
                'transaction_date' => today()->subDays(10)->toDateString(),
                'debit' => 0,
                'credit' => 18500,
                'balance' => 0,
                'remarks' => 'Demo maturity closing ledger entry',
                'created_by' => $admin->id,
            ]
        );
    }

    /**
     * @param  array<string, ChitEnrollment>  $enrollments
     */
    private function seedJewelleryInvoices(array $enrollments, User $admin): void
    {
        $closed = $enrollments['closed'];

        $invoice = JewelleryInvoice::updateOrCreate(
            ['invoice_no' => 'INV-DEMO-001'],
            [
                'customer_id' => $closed->customer_id,
                'enrollment_id' => $closed->id,
                'invoice_date' => today()->subDays(8)->toDateString(),
                'gold_rate' => 6150,
                'gross_weight' => 8.25,
                'net_weight' => 7.95,
                'making_charge' => 2500,
                'wastage' => 750,
                'gst_amount' => 1500,
                'discount' => 500,
                'chit_adjustment_amount' => 13500,
                'total_amount' => 52142.50,
                'balance_payable' => 38642.50,
                'status' => 'final',
                'created_by' => $admin->id,
                'finalized_by' => $admin->id,
                'finalized_at' => now()->subDays(8),
            ]
        );

        JewelleryInvoiceItem::updateOrCreate(
            ['invoice_id' => $invoice->id, 'item_name' => 'Demo Gold Chain'],
            [
                'purity' => '22K',
                'gross_weight' => 8.25,
                'net_weight' => 7.95,
                'rate' => 6150,
                'making_charge' => 2500,
                'wastage' => 750,
                'gst_amount' => 1500,
                'total_amount' => 52142.50,
            ]
        );

        ChitLedger::updateOrCreate(
            ['transaction_type' => 'adjustment', 'reference_type' => JewelleryInvoice::class, 'reference_id' => $invoice->id],
            [
                'enrollment_id' => $closed->id,
                'customer_id' => $closed->customer_id,
                'transaction_date' => $invoice->invoice_date,
                'debit' => 0,
                'credit' => 13500,
                'balance' => 0,
                'remarks' => 'Demo jewellery invoice chit adjustment',
                'created_by' => $admin->id,
            ]
        );
    }

    /**
     * @param  array<string, ChitPayment>  $payments
     * @param  \Illuminate\Support\Collection<string, PaymentMode>  $paymentModes
     * @param  array<string, Branch>  $branches
     * @param  array<string, User>  $users
     */
    private function seedCashbook(array $payments, $paymentModes, array $branches, array $users, User $admin): void
    {
        Cashbook::updateOrCreate(
            ['transaction_type' => 'opening_balance', 'cashbook_date' => today()->toDateString(), 'branch_id' => $branches['MAIN']->id],
            [
                'payment_mode_id' => $paymentModes['cash']->id,
                'debit' => 0,
                'credit' => 25000,
                'balance' => 25000,
                'reference_type' => 'demo_opening_balance',
                'reference_id' => 1,
                'remarks' => 'Demo opening balance',
                'created_by' => $admin->id,
            ]
        );

        foreach ($payments as $payment) {
            Cashbook::updateOrCreate(
                ['reference_type' => ChitPayment::class, 'reference_id' => $payment->id],
                [
                    'branch_id' => $payment->branch_id,
                    'cashbook_date' => $payment->payment_date,
                    'transaction_type' => match ($payment->paymentMode?->code) {
                        'upi' => 'upi_received',
                        'card' => 'card_received',
                        'bank_transfer', 'cheque' => 'bank_received',
                        default => 'cash_received',
                    },
                    'payment_mode_id' => $payment->payment_mode_id,
                    'debit' => 0,
                    'credit' => $payment->total_amount,
                    'balance' => $payment->total_amount,
                    'remarks' => 'Demo payment cashbook entry',
                    'created_by' => $admin->id,
                ]
            );
        }

        $handover = StaffCashHandover::updateOrCreate(
            ['handover_no' => 'HND-DEMO-001'],
            [
                'staff_id' => $users['staff']->id,
                'branch_id' => $branches['MAIN']->id,
                'handover_date' => today()->toDateString(),
                'cash_amount' => 3000,
                'upi_amount' => 3000,
                'card_amount' => 0,
                'bank_amount' => 0,
                'total_amount' => 6000,
                'received_by' => $users['manager']->id,
                'status' => 'received',
                'remarks' => 'Demo received handover',
            ]
        );

        Cashbook::updateOrCreate(
            ['reference_type' => StaffCashHandover::class, 'reference_id' => $handover->id],
            [
                'branch_id' => $branches['MAIN']->id,
                'cashbook_date' => today()->toDateString(),
                'transaction_type' => 'staff_handover',
                'payment_mode_id' => null,
                'debit' => 0,
                'credit' => 6000,
                'balance' => 31000,
                'remarks' => 'Demo staff cash handover',
                'created_by' => $admin->id,
            ]
        );
    }

    /**
     * @param  array<string, Customer>  $customers
     * @param  array<string, ChitEnrollment>  $enrollments
     * @param  array<string, array<int, ChitInstallment>>  $installments
     */
    private function seedMessages(array $customers, array $enrollments, array $installments): void
    {
        $customer = $customers['kumar'];
        $enrollment = $enrollments['active_overdue'];
        $installment = $installments['active_overdue'][2];
        $message = "Dear {$customer->name}, your chit installment for {$enrollment->chit_no} amount Rs. {$installment->balance_amount} is overdue.";

        Notification::updateOrCreate(
            ['customer_id' => $customer->id, 'enrollment_id' => $enrollment->id, 'notification_type' => 'due_reminder', 'channel' => 'whatsapp'],
            ['title' => 'Demo due reminder', 'message' => $message, 'status' => 'sent', 'sent_at' => now()]
        );

        WhatsappLog::updateOrCreate(
            ['customer_id' => $customer->id, 'message_type' => 'due_reminder', 'mobile' => $customer->mobile],
            ['message' => $message, 'response' => '{"provider":"placeholder"}', 'status' => 'sent', 'retry_count' => 0, 'sent_at' => now()]
        );

        SmsLog::updateOrCreate(
            ['customer_id' => $customer->id, 'message_type' => 'due_reminder', 'mobile' => $customer->mobile],
            ['message' => $message, 'response' => '{"provider":"placeholder"}', 'status' => 'failed', 'retry_count' => 1, 'sent_at' => null]
        );
    }

    /**
     * @param  array<string, Customer>  $customers
     * @param  array<string, ChitScheme>  $schemes
     * @param  array<string, ChitEnrollment>  $enrollments
     */
    private function seedOperationalLogs(User $admin, array $customers, array $schemes, array $enrollments): void
    {
        $logs = [
            ['module' => 'customers', 'action' => 'created', 'description' => 'Demo customer test data created.'],
            ['module' => 'schemes', 'action' => 'created', 'description' => 'Demo chit schemes loaded.'],
            ['module' => 'enrollments', 'action' => 'created', 'description' => 'Demo chit enrollments loaded.'],
            ['module' => 'payments', 'action' => 'created', 'description' => 'Demo payments and receipts loaded.'],
            ['module' => 'backup', 'action' => 'success', 'description' => 'Demo backup log created.'],
        ];

        foreach ($logs as $log) {
            ActivityLog::firstOrCreate(
                ['module' => $log['module'], 'action' => $log['action'], 'description' => $log['description']],
                ['user_id' => $admin->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'DemoDataSeeder']
            );
        }

        foreach ([$customers['lakshmi'], $schemes['fixed'], $enrollments['active_paid']] as $model) {
            AuditLog::firstOrCreate(
                ['auditable_type' => $model::class, 'auditable_id' => $model->id, 'event' => 'create'],
                [
                    'user_id' => $admin->id,
                    'old_values' => null,
                    'new_values' => $model->toArray(),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'DemoDataSeeder',
                ]
            );
        }

        BackupLog::updateOrCreate(
            ['backup_name' => 'demo-backup.zip'],
            [
                'file_path' => 'Laravel/demo-backup.zip',
                'disk' => 'local',
                'size' => 10240,
                'status' => 'success',
                'message' => 'Demo backup log entry.',
                'created_by' => $admin->id,
            ]
        );
    }

    private function refreshEnrollmentTotals(ChitEnrollment $enrollment): void
    {
        $totalPaid = (float) $enrollment->installments()->sum('paid_amount');

        $enrollment->update([
            'total_paid' => $totalPaid,
            'total_pending' => max(0, (float) $enrollment->total_payable - $totalPaid),
        ]);
    }
}
