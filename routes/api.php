<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChitEnrollmentController;
use App\Http\Controllers\Api\ChitSchemeController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\LedgerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

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
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('can:payments.view');

    Route::get('/receipts', [ReceiptController::class, 'index'])->middleware('can:receipts.view');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->middleware('can:receipts.view');
    Route::get('/receipts/{receipt}/download', [ReceiptController::class, 'download'])->middleware('can:receipts.pdf');

    Route::get('/ledger', [LedgerController::class, 'index'])->middleware('can:ledger.view');
});
