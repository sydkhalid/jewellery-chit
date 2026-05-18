<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\AuthController as WebAuthController;
use App\Http\Controllers\Web\ChitEnrollmentController;
use App\Http\Controllers\Web\ChitSchemeController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\InstallmentController;
use App\Http\Controllers\Web\LedgerController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\PendingDueController;
use App\Http\Controllers\Web\ReceiptController;
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
