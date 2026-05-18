<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaturityClosingApproveRequest;
use App\Http\Requests\MaturityClosingCancelRequest;
use App\Http\Requests\MaturityClosingStoreRequest;
use App\Http\Resources\ChitClosureResource;
use App\Models\ChitClosure;
use App\Models\ChitEnrollment;
use App\Models\Customer;
use App\Repositories\MaturityClosingRepository;
use App\Services\MaturityClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class MaturityClosingController extends Controller
{
    public function __construct(
        private readonly MaturityClosingRepository $closings,
        private readonly MaturityClosingService $maturityClosingService
    ) {
    }

    public function index(): View
    {
        return view('maturity-closings.index', $this->formOptions());
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->closings->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'closure_type',
            'status',
            'from_date',
            'to_date',
        ])))
            ->addColumn('customer_name', fn (ChitClosure $closure): string => $closure->customer?->name ?? '-')
            ->addColumn('chit_no', fn (ChitClosure $closure): string => $closure->enrollment?->chit_no ?? '-')
            ->addColumn('scheme_name', fn (ChitClosure $closure): string => $closure->enrollment?->scheme?->name ?? '-')
            ->addColumn('closure_type_badge', fn (ChitClosure $closure): string => $this->typeBadge($closure->closure_type))
            ->addColumn('status_badge', fn (ChitClosure $closure): string => $this->statusBadge($closure->status))
            ->addColumn('approver_name', fn (ChitClosure $closure): string => $closure->approver?->name ?? '-')
            ->addColumn('actions', fn (ChitClosure $closure): string => $this->actionButtons($closure, $user))
            ->editColumn('created_at', fn (ChitClosure $closure): string => optional($closure->created_at)->format('d M Y'))
            ->rawColumns(['closure_type_badge', 'status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('maturity-closings.create', $this->formOptions() + [
            'closure' => new ChitClosure([
                'closure_type' => 'normal',
                'deductions' => 0,
                'refund_amount' => 0,
                'jewellery_adjustment_amount' => 0,
            ]),
        ]);
    }

    public function store(MaturityClosingStoreRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $enrollment = ChitEnrollment::query()->with(['customer', 'scheme'])->findOrFail((int) $data['enrollment_id']);

        try {
            $closure = match ($data['closure_type']) {
                'normal' => $this->maturityClosingService->createNormalClosing($enrollment, $data),
                'early' => $this->maturityClosingService->createEarlyClosing($enrollment, $data),
                'defaulted' => $this->maturityClosingService->createDefaultedClosing($enrollment, $data),
                'cancelled' => $this->maturityClosingService->createCancelledClosing($enrollment, $data),
            };
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Maturity closing created successfully', $closure, route('maturity-closings.show', $closure));
    }

    public function show(ChitClosure $closure): View
    {
        $closure->load(['customer', 'enrollment.scheme', 'enrollment.branch', 'approver', 'creator', 'completer', 'canceller']);
        $summary = $this->maturityClosingService->calculationSummary($closure->enrollment, (float) $closure->deductions);

        return view('maturity-closings.show', [
            'closure' => $closure,
            'summary' => $summary,
            'refunds' => $closure->enrollment->refunds()->latest()->get(),
            'jewelleryInvoices' => $closure->enrollment->jewelleryInvoices()->latest()->get(),
        ]);
    }

    public function approve(MaturityClosingApproveRequest $request, ChitClosure $closure): JsonResponse|RedirectResponse
    {
        try {
            $closure = $this->maturityClosingService->approveClosing($closure);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Maturity closing approved successfully', $closure, route('maturity-closings.show', $closure));
    }

    public function complete(MaturityClosingApproveRequest $request, ChitClosure $closure): JsonResponse|RedirectResponse
    {
        try {
            $closure = $this->maturityClosingService->completeClosing($closure);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Maturity closing completed successfully', $closure, route('maturity-closings.show', $closure));
    }

    public function cancel(MaturityClosingCancelRequest $request, ChitClosure $closure): JsonResponse|RedirectResponse
    {
        try {
            $closure = $this->maturityClosingService->cancelClosing($closure, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Maturity closing cancelled successfully', $closure, route('maturity-closings.show', $closure));
    }

    public function calculate(ChitEnrollment $enrollment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Maturity value calculated successfully',
            'data' => $this->maturityClosingService->calculationSummary($enrollment),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'enrollments' => ChitEnrollment::query()
                ->with(['customer', 'scheme', 'installments'])
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->orderByDesc('id')
                ->get(),
            'closureTypes' => ['normal', 'early', 'defaulted', 'cancelled'],
            'statuses' => ['pending', 'approved', 'completed', 'cancelled'],
        ];
    }

    private function actionButtons(ChitClosure $closure, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('maturity.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('maturity-closings.show', $closure).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($closure->status === 'pending' && $user?->can('maturity.approve')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-success" data-maturity-action="approve" data-url="'.route('maturity-closings.approve', $closure).'" title="Approve"><i class="bi bi-check2-circle"></i></button>';
        }

        if ($closure->status === 'approved' && $user?->can('maturity.approve')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-primary" data-maturity-action="complete" data-url="'.route('maturity-closings.complete', $closure).'" title="Complete"><i class="bi bi-patch-check"></i></button>';
        }

        if (in_array($closure->status, ['pending', 'approved'], true) && $user?->can('maturity.cancel')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-maturity-action="cancel" data-url="'.route('maturity-closings.cancel', $closure).'" title="Cancel"><i class="bi bi-x-circle"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function typeBadge(string $type): string
    {
        $class = match ($type) {
            'normal' => 'success',
            'early' => 'warning',
            'defaulted' => 'danger',
            'cancelled' => 'secondary',
            default => 'light',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($type));
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'approved' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'warning',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
    }

    private function successResponse(Request $request, string $message, ?ChitClosure $closure = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'closure' => $closure ? new ChitClosureResource($closure->loadMissing(['customer', 'enrollment.scheme', 'approver', 'creator'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('maturity-closings.index'))->with('success', $message);
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
