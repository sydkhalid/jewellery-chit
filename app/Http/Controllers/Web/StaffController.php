<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffStoreRequest;
use App\Http\Requests\StaffUpdateRequest;
use App\Http\Resources\StaffResource;
use App\Models\Branch;
use App\Models\User;
use App\Repositories\StaffRepository;
use App\Services\StaffBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffRepository $staffRepository,
        private readonly StaffBranchService $staffBranchService
    ) {
    }

    public function index(): View
    {
        return view('staff.index', $this->formOptions());
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->staffRepository->getForDataTable($request->only(['branch_id', 'status', 'role'])))
            ->addColumn('role_name', fn (User $staff): string => $staff->getRoleNames()->first() ?? '-')
            ->addColumn('branch_name', fn (User $staff): string => $staff->branch?->name ?? '-')
            ->addColumn('status_badge', fn (User $staff): string => $this->statusBadge($staff->status))
            ->addColumn('actions', fn (User $staff): string => $this->actionButtons($staff, $user))
            ->editColumn('created_at', fn (User $staff): string => optional($staff->created_at)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('staff.create', $this->formOptions() + [
            'staff' => new User(['status' => 'active']),
        ]);
    }

    public function store(StaffStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $staff = $this->staffBranchService->createStaff($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Staff user created successfully', $staff, route('staff.show', $staff));
    }

    public function show(User $staff): View
    {
        $staff->load(['branch', 'roles'])->loadCount(['staffCollections', 'assignedChitEnrollments', 'staffCashHandovers']);

        return view('staff.show', [
            'staff' => $staff,
            'summary' => $this->staffBranchService->getStaffCollectionSummary($staff),
        ]);
    }

    public function edit(User $staff): View
    {
        $staff->load(['branch', 'roles']);

        return view('staff.edit', $this->formOptions() + [
            'staff' => $staff,
        ]);
    }

    public function update(StaffUpdateRequest $request, User $staff): JsonResponse|RedirectResponse
    {
        try {
            $staff = $this->staffBranchService->updateStaff($staff, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Staff user updated successfully', $staff, route('staff.show', $staff));
    }

    public function destroy(Request $request, User $staff): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('staff.delete'), 403);

        try {
            $staff = $this->staffBranchService->deleteStaff($staff);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        $message = $staff->status === 'deleted'
            ? 'Staff user deleted successfully'
            : 'Staff user has linked records and was marked inactive';

        return $this->successResponse($request, $message, $staff, route('staff.index'));
    }

    public function status(Request $request, User $staff): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('staff.edit'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            $staff = $this->staffBranchService->changeStaffStatus($staff, $data['status']);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Staff status updated successfully', $staff);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'branches' => Branch::active()->orderBy('name')->get(),
            'roles' => ['Admin', 'Manager', 'Staff'],
        ];
    }

    private function actionButtons(User $staff, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('staff.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('staff.show', $staff).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($user?->can('staff.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('staff.edit', $staff).'" title="Edit"><i class="bi bi-pencil"></i></a>';
            $nextStatus = $staff->status === 'active' ? 'inactive' : 'active';
            $icon = $staff->status === 'active' ? 'bi-person-dash' : 'bi-person-check';
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" data-staff-action="status" data-status="'.$nextStatus.'" data-url="'.route('staff.status', $staff).'" title="Mark '.ucfirst($nextStatus).'"><i class="bi '.$icon.'"></i></button>';
        }

        if ($user?->can('staff.delete')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-staff-action="delete" data-url="'.route('staff.destroy', $staff).'" title="Delete"><i class="bi bi-trash"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        return sprintf(
            '<span class="badge rounded-pill text-bg-%s">%s</span>',
            $status === 'active' ? 'success' : 'secondary',
            ucfirst($status)
        );
    }

    private function successResponse(Request $request, string $message, ?User $staff = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'staff' => $staff ? new StaffResource($staff->loadMissing(['branch', 'roles'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('staff.index'))->with('success', $message);
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
