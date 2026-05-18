<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstallmentUpdateRequest;
use App\Http\Resources\ChitInstallmentResource;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\InstallmentRepository;
use App\Services\InstallmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class InstallmentController extends Controller
{
    public function __construct(
        private readonly InstallmentRepository $installments,
        private readonly InstallmentService $installmentService
    ) {
    }

    public function index(Request $request): View
    {
        return view('installments.index', $this->filterOptions() + [
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->installments->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'from_date',
            'to_date',
            'status',
            'assigned_staff_id',
            'branch_id',
        ])))
            ->addColumn('chit_no', fn (ChitInstallment $installment): string => $installment->enrollment?->chit_no ?? '-')
            ->addColumn('customer_name', fn (ChitInstallment $installment): string => $installment->enrollment?->customer?->name ?? '-')
            ->addColumn('scheme_name', fn (ChitInstallment $installment): string => $installment->enrollment?->scheme?->name ?? '-')
            ->addColumn('branch_name', fn (ChitInstallment $installment): string => $installment->enrollment?->branch?->name ?? '-')
            ->addColumn('staff_name', fn (ChitInstallment $installment): string => $installment->enrollment?->assignedStaff?->name ?? '-')
            ->addColumn('status_badge', fn (ChitInstallment $installment): string => $this->statusBadge($installment->status))
            ->addColumn('actions', fn (ChitInstallment $installment): string => $this->actionButtons($installment, $user))
            ->editColumn('due_date', fn (ChitInstallment $installment): string => optional($installment->due_date)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function show(ChitInstallment $installment): View
    {
        $installment->load(['enrollment.customer', 'enrollment.scheme', 'enrollment.branch', 'enrollment.assignedStaff', 'payments']);

        return view('installments.show', [
            'installment' => $installment,
        ]);
    }

    public function edit(ChitInstallment $installment): View
    {
        $installment->load(['enrollment.customer', 'enrollment.scheme']);

        return view('installments.edit', [
            'installment' => $installment,
        ]);
    }

    public function update(InstallmentUpdateRequest $request, ChitInstallment $installment): JsonResponse|RedirectResponse
    {
        try {
            $installment = $this->installmentService->updateInstallment($installment, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Installment updated successfully', $installment, route('installments.show', $installment));
    }

    public function byEnrollment(ChitEnrollment $enrollment): View
    {
        $enrollment->load(['customer', 'scheme', 'branch', 'assignedStaff']);

        return view('installments.by-enrollment', [
            'enrollment' => $enrollment,
            'installments' => $this->installmentService->getEnrollmentInstallments($enrollment),
        ]);
    }

    public function regenerate(Request $request, ChitEnrollment $enrollment): JsonResponse|RedirectResponse
    {
        try {
            $this->installmentService->regenerateSchedule($enrollment);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Installment schedule regenerated successfully', null, route('chit-enrollments.installments', $enrollment));
    }

    public function markOverdue(Request $request): JsonResponse|RedirectResponse
    {
        $count = $this->installmentService->markOverdueInstallments();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} installments marked overdue",
                'data' => [
                    'count' => $count,
                ],
            ]);
        }

        return back()->with('success', "{$count} installments marked overdue");
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
        ];
    }

    private function actionButtons(ChitInstallment $installment, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('installments.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('installments.show', $installment).'" title="View"><i class="bi bi-eye"></i></a>';
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('chit-enrollments.installments', $installment->enrollment_id).'" title="Schedule"><i class="bi bi-calendar2-week"></i></a>';
        }

        if ($user?->can('installments.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('installments.edit', $installment).'" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'paid' => 'success',
            'partial', 'advance' => 'info',
            'overdue' => 'danger',
            default => 'secondary',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
    }

    private function successResponse(Request $request, string $message, ?ChitInstallment $installment = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'installment' => $installment ? new ChitInstallmentResource($installment->loadMissing(['enrollment.customer', 'enrollment.scheme'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('installments.index'))->with('success', $message);
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
