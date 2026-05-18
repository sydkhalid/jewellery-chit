<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitLedger;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\LedgerRepository;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class LedgerController extends Controller
{
    public function __construct(
        private readonly LedgerRepository $ledgers,
        private readonly LedgerService $ledgerService
    ) {
    }

    public function index(): View
    {
        return view('ledgers.index', $this->filterOptions());
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->ledgers->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'transaction_type',
            'from_date',
            'to_date',
            'branch_id',
            'staff_id',
        ])))
            ->addColumn('customer_name', fn (ChitLedger $ledger): string => $ledger->customer?->name ?? '-')
            ->addColumn('chit_no', fn (ChitLedger $ledger): string => $ledger->enrollment?->chit_no ?? '-')
            ->addColumn('transaction_type_badge', fn (ChitLedger $ledger): string => $this->typeBadge($ledger->transaction_type))
            ->addColumn('reference', fn (ChitLedger $ledger): string => $this->referenceText($ledger))
            ->addColumn('created_by_name', fn (ChitLedger $ledger): string => $ledger->creator?->name ?? '-')
            ->addColumn('actions', fn (ChitLedger $ledger): string => $this->actionButtons($ledger, $user))
            ->editColumn('transaction_date', fn (ChitLedger $ledger): string => optional($ledger->transaction_date)->format('d M Y'))
            ->rawColumns(['transaction_type_badge', 'actions'])
            ->toJson();
    }

    public function customer(Customer $customer): View
    {
        return view('ledgers.customer', $this->ledgerService->getCustomerLedger($customer));
    }

    public function chit(ChitEnrollment $enrollment): View
    {
        return view('ledgers.chit', $this->ledgerService->getChitLedger($enrollment));
    }

    public function rebuild(Request $request, ChitEnrollment $enrollment): JsonResponse|RedirectResponse
    {
        try {
            $this->ledgerService->rebuildLedger($enrollment);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ledger rebuilt successfully',
                'data' => [
                    'redirect' => route('chit-enrollments.ledger', $enrollment),
                ],
            ]);
        }

        return redirect()->route('chit-enrollments.ledger', $enrollment)->with('success', 'Ledger rebuilt successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'enrollments' => ChitEnrollment::query()->with('customer')->latest()->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'staffUsers' => User::role(['Admin', 'Manager', 'Staff'])->active()->orderBy('name')->get(),
            'transactionTypes' => [
                'due',
                'payment',
                'late_fee',
                'advance',
                'closing',
                'refund',
                'adjustment',
            ],
        ];
    }

    private function actionButtons(ChitLedger $ledger, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('ledger.customer')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('customers.ledger', $ledger->customer_id).'" title="Customer ledger"><i class="bi bi-person-lines-fill"></i></a>';
        }

        if ($user?->can('ledger.chit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('chit-enrollments.ledger', $ledger->enrollment_id).'" title="Chit ledger"><i class="bi bi-journal-text"></i></a>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function typeBadge(string $type): string
    {
        $class = match ($type) {
            'due', 'late_fee' => 'warning',
            'payment', 'advance', 'refund', 'adjustment' => 'success',
            'closing' => 'primary',
            default => 'secondary',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst(str_replace('_', ' ', $type)));
    }

    private function referenceText(ChitLedger $ledger): string
    {
        if (! $ledger->reference_type || ! $ledger->reference_id) {
            return '-';
        }

        return class_basename($ledger->reference_type).' #'.$ledger->reference_id;
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
