<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffCashHandoverRejectRequest;
use App\Http\Requests\StaffCashHandoverStoreRequest;
use App\Http\Resources\StaffCashHandoverResource;
use App\Models\Branch;
use App\Models\StaffCashHandover;
use App\Models\User;
use App\Repositories\StaffCashHandoverRepository;
use App\Services\StaffBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class StaffCashHandoverController extends Controller
{
    public function __construct(
        private readonly StaffCashHandoverRepository $handovers,
        private readonly StaffBranchService $staffBranchService
    ) {
    }

    public function index(): View
    {
        return view('staff-cash-handovers.index', $this->formOptions());
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->handovers->getForDataTable($request->only([
            'staff_id',
            'branch_id',
            'status',
            'from_date',
            'to_date',
        ])))
            ->addColumn('staff_name', fn (StaffCashHandover $handover): string => $handover->staff?->name ?? '-')
            ->addColumn('branch_name', fn (StaffCashHandover $handover): string => $handover->branch?->name ?? '-')
            ->addColumn('receiver_name', fn (StaffCashHandover $handover): string => $handover->receiver?->name ?? '-')
            ->addColumn('status_badge', fn (StaffCashHandover $handover): string => $this->statusBadge($handover->status))
            ->addColumn('actions', fn (StaffCashHandover $handover): string => $this->actionButtons($handover, $user))
            ->editColumn('handover_date', fn (StaffCashHandover $handover): string => optional($handover->handover_date)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('staff-cash-handovers.create', $this->formOptions() + [
            'handover' => new StaffCashHandover([
                'handover_date' => today(),
                'status' => 'pending',
                'cash_amount' => 0,
                'upi_amount' => 0,
                'card_amount' => 0,
                'bank_amount' => 0,
            ]),
        ]);
    }

    public function store(StaffCashHandoverStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $handover = $this->staffBranchService->createCashHandover($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Cash handover created successfully', $handover, route('staff-cash-handovers.show', $handover));
    }

    public function show(StaffCashHandover $handover): View
    {
        return view('staff-cash-handovers.show', [
            'handover' => $handover->load(['staff', 'branch', 'receiver']),
        ]);
    }

    public function receive(Request $request, StaffCashHandover $handover): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('staff_cash_handover.receive'), 403);

        try {
            $handover = $this->staffBranchService->receiveCashHandover($handover);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Cash handover received successfully', $handover);
    }

    public function reject(StaffCashHandoverRejectRequest $request, StaffCashHandover $handover): JsonResponse|RedirectResponse
    {
        try {
            $handover = $this->staffBranchService->rejectCashHandover($handover, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Cash handover rejected successfully', $handover);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'staffUsers' => User::role(['Admin', 'Manager', 'Staff'])->active()->with('branch')->orderBy('name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
        ];
    }

    private function actionButtons(StaffCashHandover $handover, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('staff_cash_handover.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('staff-cash-handovers.show', $handover).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($handover->status === 'pending' && $user?->can('staff_cash_handover.receive')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-success" data-handover-action="receive" data-url="'.route('staff-cash-handovers.receive', $handover).'" title="Receive"><i class="bi bi-check2-circle"></i></button>';
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-handover-action="reject" data-url="'.route('staff-cash-handovers.reject', $handover).'" title="Reject"><i class="bi bi-x-octagon"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'received' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
    }

    private function successResponse(Request $request, string $message, ?StaffCashHandover $handover = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'handover' => $handover ? new StaffCashHandoverResource($handover->loadMissing(['staff', 'branch', 'receiver'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('staff-cash-handovers.index'))->with('success', $message);
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
