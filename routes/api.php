<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CashbookController;
use App\Http\Controllers\Api\ChitEnrollmentController;
use App\Http\Controllers\Api\ChitSchemeController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\GoldRateController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\IntegrationWebhookController;
use App\Http\Controllers\Api\JewelleryInvoiceController;
use App\Http\Controllers\Api\LedgerController;
use App\Http\Controllers\Api\MaturityClosingController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PaymentGatewayController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PendingDueController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StaffCashHandoverController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::prefix('webhooks')->name('api.webhooks.')->group(function () {
    Route::post('/whatsapp/twilio', [IntegrationWebhookController::class, 'twilioWhatsapp'])->name('whatsapp.twilio');
    Route::get('/whatsapp/meta', [IntegrationWebhookController::class, 'metaWhatsappVerify'])->name('whatsapp.meta.verify');
    Route::post('/whatsapp/meta', [IntegrationWebhookController::class, 'metaWhatsapp'])->name('whatsapp.meta');
    Route::post('/sms/msg91', [IntegrationWebhookController::class, 'msg91Sms'])->name('sms.msg91');
    Route::post('/sms/textlocal', [IntegrationWebhookController::class, 'textlocalSms'])->name('sms.textlocal');
    Route::post('/payments/razorpay', [IntegrationWebhookController::class, 'razorpay'])->name('payments.razorpay');
    Route::post('/payments/pine-labs', [IntegrationWebhookController::class, 'pineLabs'])->name('payments.pine-labs');
    Route::post('/payments/payu', [IntegrationWebhookController::class, 'payu'])->name('payments.payu');
    Route::post('/payments/upi-qr', [IntegrationWebhookController::class, 'upiQr'])->name('payments.upi-qr');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [AuthController::class, 'profile']);

    Route::post('/messages/whatsapp', [MessageController::class, 'whatsapp'])->middleware('can:messages.send');
    Route::post('/messages/sms', [MessageController::class, 'sms'])->middleware('can:messages.send');
    Route::get('/messages/notifications', [MessageController::class, 'notifications'])->middleware('can:messages.view');
    Route::get('/messages/whatsapp-logs', [MessageController::class, 'whatsappLogs'])->middleware('can:messages.logs');
    Route::get('/messages/sms-logs', [MessageController::class, 'smsLogs'])->middleware('can:messages.logs');

    Route::get('/settings', [SettingController::class, 'index'])->middleware('can:settings.view');
    Route::get('/settings/{key}', [SettingController::class, 'show'])->middleware('can:settings.view');

    Route::get('/cashbooks', [CashbookController::class, 'index'])->middleware('can:cashbook.view');
    Route::get('/cashbooks/daily-summary', [CashbookController::class, 'dailySummary'])->middleware('can:cashbook.view');
    Route::get('/cashbooks/date-range-summary', [CashbookController::class, 'dateRangeSummary'])->middleware('can:cashbook.view');
    Route::get('/cashbooks/payment-mode-summary', [CashbookController::class, 'paymentModeSummary'])->middleware('can:cashbook.view');
    Route::get('/cashbooks/{cashbook}', [CashbookController::class, 'show'])->middleware('can:cashbook.view');

    Route::get('/reports/dashboard-summary', [ReportController::class, 'dashboardSummary'])->middleware('can:reports.view');
    Route::get('/reports/collection-summary', [ReportController::class, 'collectionSummary'])->middleware('can:reports.view');
    Route::get('/reports/pending-summary', [ReportController::class, 'pendingSummary'])->middleware('can:reports.view');
    Route::get('/reports/staff-collection-summary', [ReportController::class, 'staffCollectionSummary'])->middleware('can:reports.view');
    Route::get('/reports/branch-collection-summary', [ReportController::class, 'branchCollectionSummary'])->middleware('can:reports.view');

    Route::get('/branches', [BranchController::class, 'index'])->middleware('can:branch.view');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])->middleware('can:branch.view');
    Route::get('/branches/{branch}/collection-summary', [BranchController::class, 'collectionSummary'])->middleware('can:branch.view');

    Route::get('/staff', [StaffController::class, 'index'])->middleware('can:staff.view');
    Route::get('/staff/{staff}', [StaffController::class, 'show'])->middleware('can:staff.view');
    Route::get('/staff/{staff}/collection-summary', [StaffController::class, 'collectionSummary'])->middleware('can:staff.view');

    Route::get('/staff-cash-handovers', [StaffCashHandoverController::class, 'index'])->middleware('can:staff_cash_handover.view');
    Route::post('/staff-cash-handovers', [StaffCashHandoverController::class, 'store'])->middleware('can:staff_cash_handover.create');
    Route::get('/staff-cash-handovers/{handover}', [StaffCashHandoverController::class, 'show'])->middleware('can:staff_cash_handover.view');

    Route::get('/customers', [CustomerController::class, 'index'])->middleware('can:customers.view');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('can:customers.create');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('can:customers.view');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('can:customers.edit');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('can:customers.delete');
    Route::post('/customers/{customer}/documents', [CustomerController::class, 'uploadDocument'])->middleware('can:customers.documents');
    Route::get('/customers/{customer}/ledger', [LedgerController::class, 'customer'])->middleware('can:ledger.customer');
    Route::get('/customers/{customer}/payment-history', [CustomerController::class, 'paymentHistory'])->middleware('can:customers.view');
    Route::get('/customers/{customer}/outstanding', [CustomerController::class, 'outstanding'])->middleware('can:customers.view');

    Route::get('/schemes', [ChitSchemeController::class, 'index'])->middleware('can:schemes.view');
    Route::get('/schemes/{scheme}', [ChitSchemeController::class, 'show'])->middleware('can:schemes.view');

    Route::get('/chit-enrollments', [ChitEnrollmentController::class, 'index'])->middleware('can:enrollments.view');
    Route::post('/chit-enrollments', [ChitEnrollmentController::class, 'store'])->middleware('can:enrollments.create');
    Route::get('/chit-enrollments/{enrollment}', [ChitEnrollmentController::class, 'show'])->middleware('can:enrollments.view');
    Route::get('/chit-enrollments/{enrollment}/ledger', [LedgerController::class, 'chit'])->middleware('can:ledger.chit');

    Route::get('/installments', [InstallmentController::class, 'index'])->middleware('can:installments.view');
    Route::get('/chit-enrollments/{enrollment}/installments', [InstallmentController::class, 'byEnrollment'])->middleware('can:installments.view');

    Route::get('/payments', [PaymentController::class, 'index'])->middleware('can:payments.view');
    Route::post('/payments', [PaymentController::class, 'store'])->middleware('can:payments.create');
    Route::post('/payments/gateway/order', [PaymentGatewayController::class, 'createOrder'])->middleware('can:payments.create');
    Route::post('/payments/gateway/{transaction}/retry', [PaymentGatewayController::class, 'retry'])->middleware('can:payments.create');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('can:payments.view');

    Route::get('/receipts', [ReceiptController::class, 'index'])->middleware('can:receipts.view');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->middleware('can:receipts.view');
    Route::get('/receipts/{receipt}/download', [ReceiptController::class, 'download'])->middleware('can:receipts.pdf');

    Route::get('/ledger', [LedgerController::class, 'index'])->middleware('can:ledger.view');

    Route::get('/maturity-closings', [MaturityClosingController::class, 'index'])->middleware('can:maturity.view');
    Route::get('/maturity-closings/{closure}', [MaturityClosingController::class, 'show'])->middleware('can:maturity.view');
    Route::get('/chit-enrollments/{enrollment}/maturity-calculate', [MaturityClosingController::class, 'calculate'])->middleware('can:maturity.view');

    Route::get('/jewellery-invoices', [JewelleryInvoiceController::class, 'index'])->middleware('can:jewellery.view');
    Route::post('/jewellery-invoices', [JewelleryInvoiceController::class, 'store'])->middleware('can:jewellery.create');
    Route::get('/jewellery-invoices/{invoice}', [JewelleryInvoiceController::class, 'show'])->middleware('can:jewellery.view');

    Route::get('/gold-rates', [GoldRateController::class, 'index'])->middleware('can:gold_rates.view');
    Route::get('/gold-rates/latest', [GoldRateController::class, 'latest'])->middleware('can:gold_rates.view');
    Route::get('/gold-rates/{goldRate}', [GoldRateController::class, 'show'])->middleware('can:gold_rates.view');

    Route::get('/pending-dues', [PendingDueController::class, 'index'])->middleware('can:pending_dues.view');
    Route::get('/pending-dues/today', [PendingDueController::class, 'today'])->middleware('can:pending_dues.view');
    Route::get('/pending-dues/weekly', [PendingDueController::class, 'weekly'])->middleware('can:pending_dues.view');
    Route::get('/pending-dues/monthly', [PendingDueController::class, 'monthly'])->middleware('can:pending_dues.view');
    Route::get('/pending-dues/overdue', [PendingDueController::class, 'overdue'])->middleware('can:pending_dues.view');
    Route::get('/pending-dues/staff-summary', [PendingDueController::class, 'staffSummary'])->middleware('can:pending_dues.view');
    Route::get('/pending-dues/branch-summary', [PendingDueController::class, 'branchSummary'])->middleware('can:pending_dues.view');
});
