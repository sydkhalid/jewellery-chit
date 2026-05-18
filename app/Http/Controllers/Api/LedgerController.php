<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ChitLedgerResource;
use App\Models\ChitEnrollment;
use App\Models\Customer;
use App\Repositories\LedgerRepository;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LedgerController extends BaseApiController
{
    public function __construct(
        private readonly LedgerRepository $ledgers,
        private readonly LedgerService $ledgerService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $ledgers = $this->ledgers->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'transaction_type',
            'from_date',
            'to_date',
            'branch_id',
            'staff_id',
        ]))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Ledger fetched successfully',
            'data' => [
                'ledgers' => ChitLedgerResource::collection($ledgers->getCollection()),
                'pagination' => [
                    'current_page' => $ledgers->currentPage(),
                    'last_page' => $ledgers->lastPage(),
                    'per_page' => $ledgers->perPage(),
                    'total' => $ledgers->total(),
                ],
            ],
        ]);
    }

    public function customer(Customer $customer): JsonResponse
    {
        $ledger = $this->ledgerService->getCustomerLedger($customer);

        return response()->json([
            'success' => true,
            'message' => 'Customer ledger fetched successfully',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->name,
                    'mobile' => $customer->mobile,
                ],
                'summary' => [
                    'total_debit' => $ledger['total_debit'],
                    'total_credit' => $ledger['total_credit'],
                    'closing_balance' => $ledger['closing_balance'],
                ],
                'ledgers' => ChitLedgerResource::collection($ledger['entries']),
            ],
        ]);
    }

    public function chit(ChitEnrollment $enrollment): JsonResponse
    {
        $ledger = $this->ledgerService->getChitLedger($enrollment);

        return response()->json([
            'success' => true,
            'message' => 'Chit ledger fetched successfully',
            'data' => [
                'enrollment' => [
                    'id' => $enrollment->id,
                    'chit_no' => $enrollment->chit_no,
                    'total_payable' => $enrollment->total_payable,
                    'total_paid' => $enrollment->total_paid,
                    'total_pending' => $enrollment->total_pending,
                ],
                'summary' => [
                    'total_debit' => $ledger['total_debit'],
                    'total_credit' => $ledger['total_credit'],
                    'closing_balance' => $ledger['closing_balance'],
                    'late_fee' => $ledger['late_fee'],
                    'advance' => $ledger['advance'],
                ],
                'ledgers' => ChitLedgerResource::collection($ledger['entries']),
            ],
        ]);
    }
}
