<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChitEnrollmentCancelRequest;
use App\Http\Requests\ChitEnrollmentStoreRequest;
use App\Http\Requests\ChitEnrollmentUpdateRequest;
use App\Http\Resources\ChitEnrollmentResource;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\ChitEnrollmentRepository;
use App\Services\ChitEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ChitEnrollmentController extends Controller
{
    public function __construct(
        private readonly ChitEnrollmentRepository $enrollments,
        private readonly ChitEnrollmentService $enrollmentService
    ) {
    }

    public function index(): View
    {
        return view('chit-enrollments.index', $this->formOptions());
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->enrollments->getForDataTable($request->only([
            'customer_id',
            'scheme_id',
            'branch_id',
            'assigned_staff_id',
            'status',
            'from_date',
            'to_date',
        ])))
            ->addColumn('customer_name', fn (ChitEnrollment $enrollment): string => $enrollment->customer?->name ?? '-')
            ->addColumn('scheme_name', fn (ChitEnrollment $enrollment): string => $enrollment->scheme?->name ?? '-')
            ->addColumn('branch_name', fn (ChitEnrollment $enrollment): string => $enrollment->branch?->name ?? '-')
            ->addColumn('staff_name', fn (ChitEnrollment $enrollment): string => $enrollment->assignedStaff?->name ?? '-')
            ->addColumn('status_badge', fn (ChitEnrollment $enrollment): string => sprintf(
                '<span class="badge rounded-pill text-bg-%s">%s</span>',
                $enrollment->status === 'active' ? 'success' : ($enrollment->status === 'cancelled' ? 'danger' : 'secondary'),
                ucfirst($enrollment->status)
            ))
            ->addColumn('actions', fn (ChitEnrollment $enrollment): string => $this->actionButtons($enrollment, $user))
            ->editColumn('start_date', fn (ChitEnrollment $enrollment): string => optional($enrollment->start_date)->format('d M Y'))
            ->editColumn('maturity_date', fn (ChitEnrollment $enrollment): string => optional($enrollment->maturity_date)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('chit-enrollments.create', $this->formOptions() + [
            'enrollment' => new ChitEnrollment([
                'start_date' => now(),
                'monthly_due_date' => (int) now()->format('d'),
                'maturity_date' => now(),
                'status' => 'active',
            ]),
        ]);
    }

    public function store(ChitEnrollmentStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $enrollment = $this->enrollmentService->createEnrollment($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Enrollment created successfully', $enrollment, route('chit-enrollments.show', $enrollment));
    }

    public function show(ChitEnrollment $enrollment): View
    {
        $enrollment->load(['customer', 'scheme', 'branch', 'assignedStaff', 'installments', 'payments', 'cancellations']);

        return view('chit-enrollments.show', [
            'enrollment' => $enrollment,
        ]);
    }

    public function edit(ChitEnrollment $enrollment): View
    {
        return view('chit-enrollments.edit', $this->formOptions($enrollment) + [
            'enrollment' => $enrollment->load(['customer', 'scheme', 'branch', 'assignedStaff']),
        ]);
    }

    public function update(ChitEnrollmentUpdateRequest $request, ChitEnrollment $enrollment): JsonResponse|RedirectResponse
    {
        try {
            $enrollment = $this->enrollmentService->updateEnrollment($enrollment, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Enrollment updated successfully', $enrollment, route('chit-enrollments.show', $enrollment));
    }

    public function destroy(Request $request, ChitEnrollment $enrollment): JsonResponse|RedirectResponse
    {
        try {
            $this->enrollmentService->deleteEnrollment($enrollment);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Enrollment deleted successfully', null, route('chit-enrollments.index'));
    }

    public function cancel(ChitEnrollmentCancelRequest $request, ChitEnrollment $enrollment): JsonResponse|RedirectResponse
    {
        try {
            $enrollment = $this->enrollmentService->cancelEnrollment($enrollment, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Enrollment cancelled successfully', $enrollment, route('chit-enrollments.show', $enrollment));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?ChitEnrollment $enrollment = null): array
    {
        $schemeQuery = ChitScheme::query()->where('status', 'active');

        if ($enrollment?->scheme_id) {
            $schemeQuery->orWhere('id', $enrollment->scheme_id);
        }

        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'schemes' => $schemeQuery->orderBy('name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'staffUsers' => User::role(['Admin', 'Manager', 'Staff'])->active()->orderBy('name')->get(),
        ];
    }

    private function actionButtons(ChitEnrollment $enrollment, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('enrollments.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('chit-enrollments.show', $enrollment).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($enrollment->status === 'active' && $user?->can('enrollments.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('chit-enrollments.edit', $enrollment).'" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        if ($enrollment->status === 'active' && $user?->can('enrollments.cancel')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" data-enrollment-action="cancel" data-url="'.route('chit-enrollments.cancel', $enrollment).'" title="Cancel"><i class="bi bi-x-circle"></i></button>';
        }

        if ((int) $enrollment->payments_count === 0 && $user?->can('enrollments.delete')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-enrollment-action="delete" data-url="'.route('chit-enrollments.destroy', $enrollment).'" data-method="DELETE" title="Delete"><i class="bi bi-trash"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1">'.implode('', $buttons).'</div>';
    }

    private function successResponse(Request $request, string $message, ?ChitEnrollment $enrollment = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'enrollment' => $enrollment ? new ChitEnrollmentResource($enrollment->loadMissing(['customer', 'scheme', 'branch', 'assignedStaff'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('chit-enrollments.index'))->with('success', $message);
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
