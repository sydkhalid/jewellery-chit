<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashbookClosingBalanceRequest;
use App\Http\Requests\CashbookOpeningBalanceRequest;
use App\Http\Requests\CashbookStoreRequest;
use App\Http\Resources\CashbookResource;
use App\Models\Branch;
use App\Models\Cashbook;
use App\Models\PaymentMode;
use App\Repositories\CashbookRepository;
use App\Services\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CashbookController extends Controller
{
    public function __construct(
        private readonly CashbookRepository $cashbooks,
        private readonly CashflowService $cashflowService
    ) {
    }

    public function index(): View
    {
        return view('cashbooks.index', $this->formOptions() + [
            'summary' => $this->cashflowService->calculateDailyCashflow(today()),
            'paymentModeSummary' => $this->cashflowService->getPaymentModeSummary(today(), today()),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->cashbooks->getForDataTable($request->only([
            'branch_id',
            'transaction_type',
            'payment_mode_id',
            'from_date',
            'to_date',
        ])))
            ->addColumn('branch_name', fn (Cashbook $cashbook): string => $cashbook->branch?->name ?? '-')
            ->addColumn('payment_mode_name', fn (Cashbook $cashbook): string => $cashbook->paymentMode?->name ?? '-')
            ->addColumn('transaction_type_label', fn (Cashbook $cashbook): string => str($cashbook->transaction_type)->replace('_', ' ')->title()->toString())
            ->addColumn('creator_name', fn (Cashbook $cashbook): string => $cashbook->creator?->name ?? '-')
            ->addColumn('actions', fn (Cashbook $cashbook): string => '<a class="btn btn-sm btn-light" href="'.route('cashbooks.show', $cashbook).'" title="View"><i class="bi bi-eye"></i></a>')
            ->editColumn('cashbook_date', fn (Cashbook $cashbook): string => optional($cashbook->cashbook_date)->format('d M Y'))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('cashbooks.create', $this->formOptions() + [
            'cashbook' => new Cashbook([
                'cashbook_date' => today(),
                'transaction_type' => 'cash_received',
                'debit' => 0,
                'credit' => 0,
            ]),
        ]);
    }

    public function store(CashbookStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $cashbook = $this->cashflowService->createCashbookEntry($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Cashbook entry created successfully', $cashbook, route('cashbooks.show', $cashbook));
    }

    public function show(Cashbook $cashbook): View
    {
        return view('cashbooks.show', [
            'cashbook' => $cashbook->load(['branch', 'paymentMode', 'creator']),
        ]);
    }

    public function openingBalance(): View
    {
        return view('cashbooks.opening-balance', $this->formOptions() + [
            'cashbook' => new Cashbook([
                'cashbook_date' => today(),
                'transaction_type' => 'opening_balance',
                'debit' => 0,
                'credit' => 0,
            ]),
        ]);
    }

    public function storeOpeningBalance(CashbookOpeningBalanceRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $cashbook = $this->cashflowService->createOpeningBalance($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Opening balance created successfully', $cashbook, route('cashbooks.show', $cashbook));
    }

    public function closingBalance(): View
    {
        return view('cashbooks.closing-balance', $this->formOptions() + [
            'cashbook' => new Cashbook([
                'cashbook_date' => today(),
                'transaction_type' => 'closing_balance',
            ]),
        ]);
    }

    public function storeClosingBalance(CashbookClosingBalanceRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $cashbook = $this->cashflowService->createClosingBalance($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Closing balance created successfully', $cashbook, route('cashbooks.show', $cashbook));
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Daily cashflow summary fetched successfully',
            'data' => [
                'summary' => $this->cashflowService->calculateDailyCashflow($date, $request->input('branch_id')),
            ],
        ]);
    }

    public function dateRangeSummary(Request $request): JsonResponse
    {
        $from = $request->input('from_date', today()->toDateString());
        $to = $request->input('to_date', today()->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Date range cashflow summary fetched successfully',
            'data' => [
                'summary' => $this->cashflowService->calculateDateRangeCashflow($from, $to, $request->input('branch_id')),
                'branch_summary' => $this->cashflowService->getBranchWiseCashflow($from, $to),
            ],
        ]);
    }

    public function paymentModeSummary(Request $request): JsonResponse
    {
        $from = $request->input('from_date', today()->toDateString());
        $to = $request->input('to_date', today()->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Payment mode cashflow summary fetched successfully',
            'data' => [
                'payment_modes' => $this->cashflowService->getPaymentModeSummary($from, $to, $request->input('branch_id')),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'branches' => Branch::active()->orderBy('name')->get(),
            'paymentModes' => PaymentMode::active()->orderBy('name')->get(),
            'transactionTypes' => CashflowService::TRANSACTION_TYPES,
        ];
    }

    private function successResponse(Request $request, string $message, ?Cashbook $cashbook = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'cashbook' => $cashbook ? new CashbookResource($cashbook->loadMissing(['branch', 'paymentMode', 'creator'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('cashbooks.index'))->with('success', $message);
    }

    private function validationErrorResponse(Request $request, ValidationException $exception): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $exception->errors(),
                ],
            ], 422);
        }

        return back()->withErrors($exception->errors())->withInput();
    }
}
