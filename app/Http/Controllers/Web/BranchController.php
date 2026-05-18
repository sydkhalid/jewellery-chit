<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchStoreRequest;
use App\Http\Requests\BranchUpdateRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Repositories\BranchRepository;
use App\Services\StaffBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchRepository $branches,
        private readonly StaffBranchService $staffBranchService
    ) {
    }

    public function index(): View
    {
        return view('branches.index');
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->branches->getForDataTable($request->only(['status', 'city'])))
            ->addColumn('status_badge', fn (Branch $branch): string => $this->statusBadge($branch->status))
            ->addColumn('actions', fn (Branch $branch): string => $this->actionButtons($branch, $user))
            ->editColumn('created_at', fn (Branch $branch): string => optional($branch->created_at)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('branches.create', [
            'branch' => new Branch(['status' => 'active']),
        ]);
    }

    public function store(BranchStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $branch = $this->staffBranchService->createBranch($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Branch created successfully', $branch, route('branches.show', $branch));
    }

    public function show(Branch $branch): View
    {
        $branch->loadCount(['users', 'enrollments', 'payments', 'staffCashHandovers']);

        return view('branches.show', [
            'branch' => $branch,
            'summary' => $this->staffBranchService->getBranchCollectionSummary($branch),
        ]);
    }

    public function edit(Branch $branch): View
    {
        return view('branches.edit', [
            'branch' => $branch,
        ]);
    }

    public function update(BranchUpdateRequest $request, Branch $branch): JsonResponse|RedirectResponse
    {
        try {
            $branch = $this->staffBranchService->updateBranch($branch, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Branch updated successfully', $branch, route('branches.show', $branch));
    }

    public function destroy(Request $request, Branch $branch): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('branch.delete'), 403);

        try {
            $branch = $this->staffBranchService->deleteBranch($branch);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        $message = $branch->status === 'deleted'
            ? 'Branch deleted successfully'
            : 'Branch has linked records and was marked inactive';

        return $this->successResponse($request, $message, $branch, route('branches.index'));
    }

    private function actionButtons(Branch $branch, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('branch.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('branches.show', $branch).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($user?->can('branch.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('branches.edit', $branch).'" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        if ($user?->can('branch.delete')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-branch-action="delete" data-url="'.route('branches.destroy', $branch).'" title="Delete"><i class="bi bi-trash"></i></button>';
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

    private function successResponse(Request $request, string $message, ?Branch $branch = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'branch' => $branch ? new BranchResource($branch->loadCount(['users', 'enrollments', 'payments'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('branches.index'))->with('success', $message);
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
