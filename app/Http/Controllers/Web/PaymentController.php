<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentCancelRequest;
use App\Http\Requests\PaymentEditApprovalRequest;
use App\Http\Requests\PaymentStoreRequest;
use App\Http\Requests\PaymentUpdateRequest;
use App\Http\Resources\ChitPaymentResource;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitPayment;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly PaymentService $paymentService
    ) {
    }

    public function index(): View
    {
        return view('payments.index', $this->formOptions());
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->payments->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'payment_mode_id',
            'staff_id',
            'branch_id',
            'from_date',
            'to_date',
            'status',
        ])))
            ->addColumn('customer_name', fn (ChitPayment $payment): string => $payment->customer?->name ?? '-')
            ->addColumn('chit_no', fn (ChitPayment $payment): string => $payment->enrollment?->chit_no ?? '-')
            ->addColumn('payment_mode_name', fn (ChitPayment $payment): string => $payment->paymentMode?->name ?? '-')
            ->addColumn('staff_name', fn (ChitPayment $payment): string => $payment->staff?->name ?? '-')
            ->addColumn('branch_name', fn (ChitPayment $payment): string => $payment->branch?->name ?? '-')
            ->addColumn('receipt_no', fn (ChitPayment $payment): string => $payment->receipt?->receipt_no ?? '-')
            ->addColumn('status_badge', fn (ChitPayment $payment): string => $this->statusBadge($payment->status))
            ->addColumn('actions', fn (ChitPayment $payment): string => $this->actionButtons($payment, $user))
            ->editColumn('payment_date', fn (ChitPayment $payment): string => optional($payment->payment_date)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('payments.create', $this->formOptions() + [
            'payment' => new ChitPayment([
                'payment_date' => now(),
                'payment_type' => 'partial',
                'status' => 'success',
            ]),
        ]);
    }

    public function store(PaymentStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $payment = $this->paymentService->collectPayment($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Payment collected successfully', $payment, route('payments.show', $payment));
    }

    public function show(ChitPayment $payment): View
    {
        $payment->load(['customer', 'enrollment.scheme', 'paymentMode', 'branch', 'staff', 'receipt', 'allocations.installment']);

        return view('payments.show', [
            'payment' => $payment,
        ]);
    }

    public function edit(ChitPayment $payment): View
    {
        $payment->load(['customer', 'enrollment', 'paymentMode', 'branch', 'staff', 'allocations.installment']);

        return view('payments.edit', $this->formOptions() + [
            'payment' => $payment,
        ]);
    }

    public function update(PaymentUpdateRequest $request, ChitPayment $payment): JsonResponse|RedirectResponse
    {
        try {
            $payment = $payment->status === 'success'
                ? $this->paymentService->requestPaymentEditApproval($payment, $request->validated())
                : $this->paymentService->updatePendingPayment($payment, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        $message = $payment->edit_status === 'pending'
            ? 'Payment edit approval requested successfully'
            : 'Payment updated successfully';

        return $this->successResponse($request, $message, $payment, route('payments.show', $payment));
    }

    public function cancel(PaymentCancelRequest $request, ChitPayment $payment): JsonResponse|RedirectResponse
    {
        try {
            $payment = $this->paymentService->cancelPayment($payment, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Payment cancelled successfully', $payment, route('payments.show', $payment));
    }

    public function approveEdit(PaymentEditApprovalRequest $request, ChitPayment $payment): JsonResponse|RedirectResponse
    {
        try {
            $payment = $this->paymentService->approvePaymentEdit($payment, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Payment edit approval processed successfully', $payment, route('payments.show', $payment));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'enrollments' => ChitEnrollment::query()
                ->with(['customer', 'scheme', 'installments' => fn ($query) => $query->where('balance_amount', '>', 0)->orderBy('installment_no')])
                ->where('status', 'active')
                ->orderByDesc('id')
                ->get(),
            'paymentModes' => PaymentMode::active()->orderBy('name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'staffUsers' => User::role(['Admin', 'Manager', 'Staff'])->active()->orderBy('name')->get(),
        ];
    }

    private function actionButtons(ChitPayment $payment, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('payments.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('payments.show', $payment).'" title="View"><i class="bi bi-eye"></i></a>';
            if ($payment->receipt && $user?->can('receipts.view')) {
                $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('receipts.show', $payment->receipt).'" title="Receipt"><i class="bi bi-receipt"></i></a>';
            }
        }

        if ($payment->status === 'success' && $user?->can('payments.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('payments.edit', $payment).'" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        if ($payment->status === 'success' && $payment->edit_status === 'pending' && $user?->can('payments.approve_edit')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-success" data-payment-action="approve" data-url="'.route('payments.approve-edit', $payment).'" title="Approve edit"><i class="bi bi-check2-circle"></i></button>';
        }

        if ($payment->status === 'success' && $user?->can('payments.cancel')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-payment-action="cancel" data-url="'.route('payments.cancel', $payment).'" title="Cancel"><i class="bi bi-x-circle"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'success' => 'success',
            'pending' => 'warning',
            'cancelled' => 'danger',
            default => 'secondary',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
    }

    private function successResponse(Request $request, string $message, ?ChitPayment $payment = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'payment' => $payment ? new ChitPaymentResource($payment->loadMissing(['customer', 'enrollment.scheme', 'paymentMode', 'branch', 'staff', 'receipt', 'allocations.installment'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('payments.index'))->with('success', $message);
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
