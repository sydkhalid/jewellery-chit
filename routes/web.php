<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ActivityLogController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\AuthController as WebAuthController;
use App\Http\Controllers\Web\BackupController;
use App\Http\Controllers\Web\BranchController;
use App\Http\Controllers\Web\CashbookController;
use App\Http\Controllers\Web\ChitEnrollmentController;
use App\Http\Controllers\Web\ChitSchemeController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GoldRateController;
use App\Http\Controllers\Web\InstallmentController;
use App\Http\Controllers\Web\JewelleryInvoiceController;
use App\Http\Controllers\Web\LedgerController;
use App\Http\Controllers\Web\MaturityClosingController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\PendingDueController;
use App\Http\Controllers\Web\ReceiptController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\SmsLogController;
use App\Http\Controllers\Web\StaffCashHandoverController;
use App\Http\Controllers\Web\StaffController;
use App\Http\Controllers\Web\WhatsappLogController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login']);
});

Route::post('/logout', [WebAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:Admin|Manager|Staff', 'permission:dashboard.view'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:Admin|Manager|Staff'])->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('settings.index');
    Route::post('/settings/update', [SettingController::class, 'update'])
        ->middleware(['permission:settings.edit', 'role:Admin|Manager'])
        ->name('settings.update');
    Route::get('/settings/shop', [SettingController::class, 'shop'])
        ->middleware('permission:settings.view')
        ->name('settings.shop');
    Route::get('/settings/receipt', [SettingController::class, 'receipt'])
        ->middleware('permission:settings.view')
        ->name('settings.receipt');
    Route::get('/settings/chit', [SettingController::class, 'chit'])
        ->middleware('permission:settings.view')
        ->name('settings.chit');
    Route::get('/settings/message', [SettingController::class, 'message'])
        ->middleware('permission:settings.view')
        ->name('settings.message');
    Route::get('/settings/backup', [SettingController::class, 'backup'])
        ->middleware('permission:settings.backup')
        ->name('settings.backup');

    Route::get('/backups', [BackupController::class, 'index'])
        ->middleware('permission:backup.view')
        ->name('backups.index');
    Route::post('/backups/create', [BackupController::class, 'create'])
        ->middleware('permission:backup.create')
        ->name('backups.create');
    Route::get('/backups/{backup}/download', [BackupController::class, 'download'])
        ->middleware('permission:backup.download')
        ->name('backups.download');
    Route::delete('/backups/{backup}', [BackupController::class, 'delete'])
        ->middleware('permission:backup.delete')
        ->name('backups.delete');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:audit_logs.view')
        ->name('audit-logs.index');
    Route::get('/audit-logs/data', [AuditLogController::class, 'data'])
        ->middleware('permission:audit_logs.view')
        ->name('audit-logs.data');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])
        ->middleware('permission:audit_logs.view')
        ->name('audit-logs.show');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:activity_logs.view')
        ->name('activity-logs.index');
    Route::get('/activity-logs/data', [ActivityLogController::class, 'data'])
        ->middleware('permission:activity_logs.view')
        ->name('activity-logs.data');
    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
        ->middleware('permission:activity_logs.view')
        ->name('activity-logs.show');

    Route::get('/messages', [NotificationController::class, 'index'])
        ->middleware('permission:messages.view')
        ->name('messages.index');
    Route::get('/messages/whatsapp-logs', [WhatsappLogController::class, 'index'])
        ->middleware('permission:messages.logs')
        ->name('messages.whatsapp-logs');
    Route::get('/messages/sms-logs', [SmsLogController::class, 'index'])
        ->middleware('permission:messages.logs')
        ->name('messages.sms-logs');
    Route::get('/messages/notifications', [NotificationController::class, 'notifications'])
        ->middleware('permission:messages.view')
        ->name('messages.notifications');
    Route::post('/messages/send-whatsapp', [NotificationController::class, 'sendWhatsapp'])
        ->middleware('permission:messages.send')
        ->name('messages.send-whatsapp');
    Route::post('/messages/send-sms', [NotificationController::class, 'sendSms'])
        ->middleware('permission:messages.send')
        ->name('messages.send-sms');
    Route::post('/whatsapp-logs/{log}/retry', [WhatsappLogController::class, 'retry'])
        ->middleware('permission:messages.retry')
        ->name('whatsapp-logs.retry');
    Route::post('/sms-logs/{log}/retry', [SmsLogController::class, 'retry'])
        ->middleware('permission:messages.retry')
        ->name('sms-logs.retry');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view')
        ->name('reports.index');
    Route::get('/reports/customers', [ReportController::class, 'customers'])
        ->middleware('permission:reports.view')
        ->name('reports.customers');
    Route::get('/reports/active-chits', [ReportController::class, 'activeChits'])
        ->middleware('permission:reports.view')
        ->name('reports.active-chits');
    Route::get('/reports/collections', [ReportController::class, 'collections'])
        ->middleware('permission:reports.view')
        ->name('reports.collections');
    Route::get('/reports/pending', [ReportController::class, 'pending'])
        ->middleware('permission:reports.view')
        ->name('reports.pending');
    Route::get('/reports/overdue', [ReportController::class, 'overdue'])
        ->middleware('permission:reports.view')
        ->name('reports.overdue');
    Route::get('/reports/matured', [ReportController::class, 'matured'])
        ->middleware('permission:reports.view')
        ->name('reports.matured');
    Route::get('/reports/closed', [ReportController::class, 'closed'])
        ->middleware('permission:reports.view')
        ->name('reports.closed');
    Route::get('/reports/cancelled', [ReportController::class, 'cancelled'])
        ->middleware('permission:reports.view')
        ->name('reports.cancelled');
    Route::get('/reports/staff', [ReportController::class, 'staff'])
        ->middleware('permission:reports.view')
        ->name('reports.staff');
    Route::get('/reports/branches', [ReportController::class, 'branches'])
        ->middleware('permission:reports.view')
        ->name('reports.branches');
    Route::get('/reports/schemes', [ReportController::class, 'schemes'])
        ->middleware('permission:reports.view')
        ->name('reports.schemes');
    Route::get('/reports/receipts', [ReportController::class, 'receipts'])
        ->middleware('permission:reports.view')
        ->name('reports.receipts');
    Route::get('/reports/cashflow', [ReportController::class, 'cashflow'])
        ->middleware('permission:reports.view')
        ->name('reports.cashflow');
    Route::get('/reports/{type}/excel', [ReportController::class, 'exportExcel'])
        ->middleware('permission:reports.export_excel')
        ->name('reports.excel');
    Route::get('/reports/{type}/pdf', [ReportController::class, 'exportPdf'])
        ->middleware('permission:reports.export_pdf')
        ->name('reports.pdf');
    Route::get('/reports/{type}/print', [ReportController::class, 'printReport'])
        ->middleware('permission:reports.print')
        ->name('reports.print');

    Route::get('/cashbooks', [CashbookController::class, 'index'])
        ->middleware('permission:cashbook.view')
        ->name('cashbooks.index');
    Route::get('/cashbooks/data', [CashbookController::class, 'data'])
        ->middleware('permission:cashbook.view')
        ->name('cashbooks.data');
    Route::get('/cashbooks/create', [CashbookController::class, 'create'])
        ->middleware('permission:cashflow.create')
        ->name('cashbooks.create');
    Route::post('/cashbooks', [CashbookController::class, 'store'])
        ->middleware('permission:cashflow.create')
        ->name('cashbooks.store');
    Route::get('/cashbooks/opening-balance/create', [CashbookController::class, 'openingBalance'])
        ->middleware('permission:cashflow.create')
        ->name('cashbooks.opening-balance.create');
    Route::post('/cashbooks/opening-balance', [CashbookController::class, 'storeOpeningBalance'])
        ->middleware('permission:cashflow.create')
        ->name('cashbooks.opening-balance.store');
    Route::get('/cashbooks/closing-balance/create', [CashbookController::class, 'closingBalance'])
        ->middleware('permission:cashflow.create')
        ->name('cashbooks.closing-balance.create');
    Route::post('/cashbooks/closing-balance', [CashbookController::class, 'storeClosingBalance'])
        ->middleware('permission:cashflow.create')
        ->name('cashbooks.closing-balance.store');
    Route::get('/cashbooks/daily-summary', [CashbookController::class, 'dailySummary'])
        ->middleware('permission:cashbook.view')
        ->name('cashbooks.daily-summary');
    Route::get('/cashbooks/date-range-summary', [CashbookController::class, 'dateRangeSummary'])
        ->middleware('permission:cashbook.view')
        ->name('cashbooks.date-range-summary');
    Route::get('/cashbooks/payment-mode-summary', [CashbookController::class, 'paymentModeSummary'])
        ->middleware('permission:cashbook.view')
        ->name('cashbooks.payment-mode-summary');
    Route::get('/cashbooks/{cashbook}', [CashbookController::class, 'show'])
        ->middleware('permission:cashbook.view')
        ->name('cashbooks.show');

    Route::get('/branches', [BranchController::class, 'index'])
        ->middleware('permission:branch.view')
        ->name('branches.index');
    Route::get('/branches/data', [BranchController::class, 'data'])
        ->middleware('permission:branch.view')
        ->name('branches.data');
    Route::get('/branches/create', [BranchController::class, 'create'])
        ->middleware('permission:branch.create')
        ->name('branches.create');
    Route::post('/branches', [BranchController::class, 'store'])
        ->middleware('permission:branch.create')
        ->name('branches.store');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])
        ->middleware('permission:branch.view')
        ->name('branches.show');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])
        ->middleware('permission:branch.edit')
        ->name('branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])
        ->middleware('permission:branch.edit')
        ->name('branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
        ->middleware('permission:branch.delete')
        ->name('branches.destroy');

    Route::get('/staff', [StaffController::class, 'index'])
        ->middleware('permission:staff.view')
        ->name('staff.index');
    Route::get('/staff/data', [StaffController::class, 'data'])
        ->middleware('permission:staff.view')
        ->name('staff.data');
    Route::get('/staff/create', [StaffController::class, 'create'])
        ->middleware('permission:staff.create')
        ->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])
        ->middleware('permission:staff.create')
        ->name('staff.store');
    Route::get('/staff/{staff}', [StaffController::class, 'show'])
        ->middleware('permission:staff.view')
        ->name('staff.show');
    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])
        ->middleware('permission:staff.edit')
        ->name('staff.edit');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])
        ->middleware('permission:staff.edit')
        ->name('staff.update');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])
        ->middleware('permission:staff.delete')
        ->name('staff.destroy');
    Route::post('/staff/{staff}/status', [StaffController::class, 'status'])
        ->middleware('permission:staff.edit')
        ->name('staff.status');

    Route::get('/staff-cash-handovers', [StaffCashHandoverController::class, 'index'])
        ->middleware('permission:staff_cash_handover.view')
        ->name('staff-cash-handovers.index');
    Route::get('/staff-cash-handovers/data', [StaffCashHandoverController::class, 'data'])
        ->middleware('permission:staff_cash_handover.view')
        ->name('staff-cash-handovers.data');
    Route::get('/staff-cash-handovers/create', [StaffCashHandoverController::class, 'create'])
        ->middleware('permission:staff_cash_handover.create')
        ->name('staff-cash-handovers.create');
    Route::post('/staff-cash-handovers', [StaffCashHandoverController::class, 'store'])
        ->middleware('permission:staff_cash_handover.create')
        ->name('staff-cash-handovers.store');
    Route::get('/staff-cash-handovers/{handover}', [StaffCashHandoverController::class, 'show'])
        ->middleware('permission:staff_cash_handover.view')
        ->name('staff-cash-handovers.show');
    Route::post('/staff-cash-handovers/{handover}/receive', [StaffCashHandoverController::class, 'receive'])
        ->middleware('permission:staff_cash_handover.receive')
        ->name('staff-cash-handovers.receive');
    Route::post('/staff-cash-handovers/{handover}/reject', [StaffCashHandoverController::class, 'reject'])
        ->middleware('permission:staff_cash_handover.receive')
        ->name('staff-cash-handovers.reject');

    Route::get('/gold-rates', [GoldRateController::class, 'index'])
        ->middleware('permission:gold_rates.view')
        ->name('gold-rates.index');
    Route::get('/gold-rates/data', [GoldRateController::class, 'data'])
        ->middleware('permission:gold_rates.view')
        ->name('gold-rates.data');
    Route::get('/gold-rates/create', [GoldRateController::class, 'create'])
        ->middleware('permission:gold_rates.create')
        ->name('gold-rates.create');
    Route::post('/gold-rates', [GoldRateController::class, 'store'])
        ->middleware('permission:gold_rates.create')
        ->name('gold-rates.store');
    Route::get('/gold-rates/latest', [GoldRateController::class, 'latest'])
        ->middleware('permission:gold_rates.view')
        ->name('gold-rates.latest');
    Route::get('/gold-rates/{goldRate}', [GoldRateController::class, 'show'])
        ->middleware('permission:gold_rates.view')
        ->name('gold-rates.show');
    Route::get('/gold-rates/{goldRate}/edit', [GoldRateController::class, 'edit'])
        ->middleware('permission:gold_rates.edit')
        ->name('gold-rates.edit');
    Route::put('/gold-rates/{goldRate}', [GoldRateController::class, 'update'])
        ->middleware('permission:gold_rates.edit')
        ->name('gold-rates.update');
    Route::post('/gold-rates/{goldRate}/approve', [GoldRateController::class, 'approve'])
        ->middleware('permission:gold_rates.approve')
        ->name('gold-rates.approve');
    Route::post('/gold-rates/{goldRate}/reject', [GoldRateController::class, 'reject'])
        ->middleware('permission:gold_rates.approve')
        ->name('gold-rates.reject');
    Route::post('/gold-rates/{goldRate}/lock', [GoldRateController::class, 'lock'])
        ->middleware('permission:gold_rates.lock')
        ->name('gold-rates.lock');

    Route::get('/jewellery-invoices', [JewelleryInvoiceController::class, 'index'])
        ->middleware('permission:jewellery.view')
        ->name('jewellery-invoices.index');
    Route::get('/jewellery-invoices/data', [JewelleryInvoiceController::class, 'data'])
        ->middleware('permission:jewellery.view')
        ->name('jewellery-invoices.data');
    Route::get('/jewellery-invoices/create', [JewelleryInvoiceController::class, 'create'])
        ->middleware('permission:jewellery.create')
        ->name('jewellery-invoices.create');
    Route::post('/jewellery-invoices', [JewelleryInvoiceController::class, 'store'])
        ->middleware('permission:jewellery.create')
        ->name('jewellery-invoices.store');
    Route::post('/jewellery-invoices/calculate', [JewelleryInvoiceController::class, 'calculate'])
        ->middleware('permission:jewellery.create')
        ->name('jewellery-invoices.calculate');
    Route::get('/jewellery-invoices/{invoice}', [JewelleryInvoiceController::class, 'show'])
        ->middleware('permission:jewellery.view')
        ->name('jewellery-invoices.show');
    Route::get('/jewellery-invoices/{invoice}/edit', [JewelleryInvoiceController::class, 'edit'])
        ->middleware('permission:jewellery.edit')
        ->name('jewellery-invoices.edit');
    Route::put('/jewellery-invoices/{invoice}', [JewelleryInvoiceController::class, 'update'])
        ->middleware('permission:jewellery.edit')
        ->name('jewellery-invoices.update');
    Route::post('/jewellery-invoices/{invoice}/finalize', [JewelleryInvoiceController::class, 'finalize'])
        ->middleware('permission:jewellery.create')
        ->name('jewellery-invoices.finalize');
    Route::post('/jewellery-invoices/{invoice}/cancel', [JewelleryInvoiceController::class, 'cancel'])
        ->middleware('permission:jewellery.cancel')
        ->name('jewellery-invoices.cancel');

    Route::get('/maturity-closings', [MaturityClosingController::class, 'index'])
        ->middleware('permission:maturity.view')
        ->name('maturity-closings.index');
    Route::get('/maturity-closings/data', [MaturityClosingController::class, 'data'])
        ->middleware('permission:maturity.view')
        ->name('maturity-closings.data');
    Route::get('/maturity-closings/create', [MaturityClosingController::class, 'create'])
        ->middleware('permission:maturity.create')
        ->name('maturity-closings.create');
    Route::post('/maturity-closings', [MaturityClosingController::class, 'store'])
        ->middleware('permission:maturity.create')
        ->name('maturity-closings.store');
    Route::get('/maturity-closings/{closure}', [MaturityClosingController::class, 'show'])
        ->middleware('permission:maturity.view')
        ->name('maturity-closings.show');
    Route::post('/maturity-closings/{closure}/approve', [MaturityClosingController::class, 'approve'])
        ->middleware('permission:maturity.approve')
        ->name('maturity-closings.approve');
    Route::post('/maturity-closings/{closure}/complete', [MaturityClosingController::class, 'complete'])
        ->middleware('permission:maturity.approve')
        ->name('maturity-closings.complete');
    Route::post('/maturity-closings/{closure}/cancel', [MaturityClosingController::class, 'cancel'])
        ->middleware('permission:maturity.cancel')
        ->name('maturity-closings.cancel');

    Route::get('/pending-dues', [PendingDueController::class, 'index'])
        ->middleware('permission:pending_dues.view')
        ->name('pending-dues.index');
    Route::get('/pending-dues/data', [PendingDueController::class, 'data'])
        ->middleware('permission:pending_dues.view')
        ->name('pending-dues.data');
    Route::get('/pending-dues/today', [PendingDueController::class, 'today'])
        ->middleware('permission:pending_dues.view')
        ->name('pending-dues.today');
    Route::get('/pending-dues/weekly', [PendingDueController::class, 'weekly'])
        ->middleware('permission:pending_dues.view')
        ->name('pending-dues.weekly');
    Route::get('/pending-dues/monthly', [PendingDueController::class, 'monthly'])
        ->middleware('permission:pending_dues.view')
        ->name('pending-dues.monthly');
    Route::get('/pending-dues/overdue', [PendingDueController::class, 'overdue'])
        ->middleware('permission:pending_dues.view')
        ->name('pending-dues.overdue');
    Route::post('/pending-dues/bulk-reminder', [PendingDueController::class, 'bulkReminder'])
        ->middleware('permission:pending_dues.reminder')
        ->name('pending-dues.bulk-reminder');
    Route::post('/pending-dues/{installment}/followup', [PendingDueController::class, 'followup'])
        ->middleware('permission:pending_dues.followup')
        ->name('pending-dues.followup');
    Route::post('/pending-dues/{installment}/reminder', [PendingDueController::class, 'sendReminder'])
        ->middleware('permission:pending_dues.reminder')
        ->name('pending-dues.reminder');

    Route::get('/ledgers', [LedgerController::class, 'index'])
        ->middleware('permission:ledger.view')
        ->name('ledgers.index');
    Route::get('/ledgers/data', [LedgerController::class, 'data'])
        ->middleware('permission:ledger.view')
        ->name('ledgers.data');

    Route::get('/receipts', [ReceiptController::class, 'index'])
        ->middleware('permission:receipts.view')
        ->name('receipts.index');
    Route::get('/receipts/data', [ReceiptController::class, 'data'])
        ->middleware('permission:receipts.view')
        ->name('receipts.data');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])
        ->middleware('permission:receipts.view')
        ->name('receipts.show');
    Route::get('/receipts/{receipt}/thermal-print', [ReceiptController::class, 'printThermal'])
        ->middleware('permission:receipts.print')
        ->name('receipts.thermal-print');
    Route::get('/receipts/{receipt}/a4-print', [ReceiptController::class, 'printA4'])
        ->middleware('permission:receipts.print')
        ->name('receipts.a4-print');
    Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'downloadPdf'])
        ->middleware('permission:receipts.pdf')
        ->name('receipts.pdf');
    Route::get('/receipts/{receipt}/duplicate', [ReceiptController::class, 'duplicate'])
        ->middleware('permission:receipts.duplicate')
        ->name('receipts.duplicate');
    Route::post('/receipts/{receipt}/cancel', [ReceiptController::class, 'cancel'])
        ->middleware('permission:receipts.cancel')
        ->name('receipts.cancel');
    Route::post('/receipts/{receipt}/whatsapp', [ReceiptController::class, 'whatsapp'])
        ->middleware('permission:receipts.whatsapp')
        ->name('receipts.whatsapp');

    Route::get('/payments', [PaymentController::class, 'index'])
        ->middleware('permission:payments.view')
        ->name('payments.index');
    Route::get('/payments/data', [PaymentController::class, 'data'])
        ->middleware('permission:payments.view')
        ->name('payments.data');
    Route::get('/payments/create', [PaymentController::class, 'create'])
        ->middleware('permission:payments.create')
        ->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])
        ->middleware('permission:payments.create')
        ->name('payments.store');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])
        ->middleware('permission:payments.view')
        ->name('payments.show');
    Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])
        ->middleware('permission:payments.edit')
        ->name('payments.edit');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])
        ->middleware('permission:payments.edit')
        ->name('payments.update');
    Route::post('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])
        ->middleware('permission:payments.cancel')
        ->name('payments.cancel');
    Route::post('/payments/{payment}/approve-edit', [PaymentController::class, 'approveEdit'])
        ->middleware('permission:payments.approve_edit')
        ->name('payments.approve-edit');

    Route::get('/installments', [InstallmentController::class, 'index'])
        ->middleware('permission:installments.view')
        ->name('installments.index');
    Route::get('/installments/data', [InstallmentController::class, 'data'])
        ->middleware('permission:installments.view')
        ->name('installments.data');
    Route::post('/installments/mark-overdue', [InstallmentController::class, 'markOverdue'])
        ->middleware('permission:installments.status')
        ->name('installments.mark-overdue');
    Route::get('/installments/{installment}', [InstallmentController::class, 'show'])
        ->middleware('permission:installments.view')
        ->name('installments.show');
    Route::get('/installments/{installment}/edit', [InstallmentController::class, 'edit'])
        ->middleware('permission:installments.edit')
        ->name('installments.edit');
    Route::put('/installments/{installment}', [InstallmentController::class, 'update'])
        ->middleware('permission:installments.edit')
        ->name('installments.update');

    Route::get('/chit-enrollments', [ChitEnrollmentController::class, 'index'])
        ->middleware('permission:enrollments.view')
        ->name('chit-enrollments.index');
    Route::get('/chit-enrollments/data', [ChitEnrollmentController::class, 'data'])
        ->middleware('permission:enrollments.view')
        ->name('chit-enrollments.data');
    Route::get('/chit-enrollments/create', [ChitEnrollmentController::class, 'create'])
        ->middleware('permission:enrollments.create')
        ->name('chit-enrollments.create');
    Route::post('/chit-enrollments', [ChitEnrollmentController::class, 'store'])
        ->middleware('permission:enrollments.create')
        ->name('chit-enrollments.store');
    Route::get('/chit-enrollments/{enrollment}', [ChitEnrollmentController::class, 'show'])
        ->middleware('permission:enrollments.view')
        ->name('chit-enrollments.show');
    Route::get('/chit-enrollments/{enrollment}/edit', [ChitEnrollmentController::class, 'edit'])
        ->middleware('permission:enrollments.edit')
        ->name('chit-enrollments.edit');
    Route::put('/chit-enrollments/{enrollment}', [ChitEnrollmentController::class, 'update'])
        ->middleware('permission:enrollments.edit')
        ->name('chit-enrollments.update');
    Route::delete('/chit-enrollments/{enrollment}', [ChitEnrollmentController::class, 'destroy'])
        ->middleware('permission:enrollments.delete')
        ->name('chit-enrollments.destroy');
    Route::post('/chit-enrollments/{enrollment}/cancel', [ChitEnrollmentController::class, 'cancel'])
        ->middleware('permission:enrollments.cancel')
        ->name('chit-enrollments.cancel');
    Route::get('/chit-enrollments/{enrollment}/installments', [InstallmentController::class, 'byEnrollment'])
        ->middleware('permission:installments.view')
        ->name('chit-enrollments.installments');
    Route::post('/chit-enrollments/{enrollment}/installments/regenerate', [InstallmentController::class, 'regenerate'])
        ->middleware('permission:installments.generate')
        ->name('chit-enrollments.installments.regenerate');
    Route::get('/chit-enrollments/{enrollment}/ledger', [LedgerController::class, 'chit'])
        ->middleware('permission:ledger.chit')
        ->name('chit-enrollments.ledger');
    Route::post('/chit-enrollments/{enrollment}/ledger/rebuild', [LedgerController::class, 'rebuild'])
        ->middleware(['permission:ledger.chit', 'role:Admin'])
        ->name('chit-enrollments.ledger.rebuild');
    Route::get('/chit-enrollments/{enrollment}/maturity-calculate', [MaturityClosingController::class, 'calculate'])
        ->middleware('permission:maturity.view')
        ->name('chit-enrollments.maturity-calculate');

    Route::get('/chit-schemes', [ChitSchemeController::class, 'index'])
        ->middleware('permission:schemes.view')
        ->name('chit-schemes.index');
    Route::get('/chit-schemes/data', [ChitSchemeController::class, 'data'])
        ->middleware('permission:schemes.view')
        ->name('chit-schemes.data');
    Route::get('/chit-schemes/create', [ChitSchemeController::class, 'create'])
        ->middleware('permission:schemes.create')
        ->name('chit-schemes.create');
    Route::post('/chit-schemes', [ChitSchemeController::class, 'store'])
        ->middleware('permission:schemes.create')
        ->name('chit-schemes.store');
    Route::get('/chit-schemes/{scheme}', [ChitSchemeController::class, 'show'])
        ->middleware('permission:schemes.view')
        ->name('chit-schemes.show');
    Route::get('/chit-schemes/{scheme}/edit', [ChitSchemeController::class, 'edit'])
        ->middleware('permission:schemes.edit')
        ->name('chit-schemes.edit');
    Route::put('/chit-schemes/{scheme}', [ChitSchemeController::class, 'update'])
        ->middleware('permission:schemes.edit')
        ->name('chit-schemes.update');
    Route::delete('/chit-schemes/{scheme}', [ChitSchemeController::class, 'destroy'])
        ->middleware('permission:schemes.delete')
        ->name('chit-schemes.destroy');
    Route::patch('/chit-schemes/{scheme}/status', [ChitSchemeController::class, 'changeStatus'])
        ->middleware('permission:schemes.status')
        ->name('chit-schemes.status');

    Route::get('/customers', [CustomerController::class, 'index'])
        ->middleware('permission:customers.view')
        ->name('customers.index');
    Route::get('/customers/data', [CustomerController::class, 'data'])
        ->middleware('permission:customers.view')
        ->name('customers.data');
    Route::get('/customers/{customer}/matured-chits', [JewelleryInvoiceController::class, 'getCustomerMaturedChits'])
        ->middleware('permission:jewellery.adjust_chit')
        ->name('customers.matured-chits');
    Route::get('/customers/create', [CustomerController::class, 'create'])
        ->middleware('permission:customers.create')
        ->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('permission:customers.create')
        ->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('permission:customers.view')
        ->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->middleware('permission:customers.edit')
        ->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.edit')
        ->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->name('customers.destroy');
    Route::patch('/customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])
        ->middleware('permission:customers.deactivate')
        ->name('customers.deactivate');
    Route::post('/customers/{customer}/documents', [CustomerController::class, 'uploadDocument'])
        ->middleware('permission:customers.documents')
        ->name('customers.documents.store');
    Route::get('/customers/{customer}/ledger', [LedgerController::class, 'customer'])
        ->middleware('permission:ledger.customer')
        ->name('customers.ledger');
    Route::get('/customers/{customer}/payment-history', [CustomerController::class, 'paymentHistory'])
        ->middleware('permission:customers.view')
        ->name('customers.payment-history');
    Route::get('/customers/{customer}/outstanding', [CustomerController::class, 'outstanding'])
        ->middleware('permission:customers.view')
        ->name('customers.outstanding');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
