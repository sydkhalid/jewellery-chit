<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChitSchemeStoreRequest;
use App\Http\Requests\ChitSchemeUpdateRequest;
use App\Http\Resources\ChitSchemeResource;
use App\Models\ChitScheme;
use App\Repositories\ChitSchemeRepository;
use App\Services\ChitSchemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ChitSchemeController extends Controller
{
    public function __construct(
        private readonly ChitSchemeRepository $schemes,
        private readonly ChitSchemeService $schemeService
    ) {
    }

    public function index(): View
    {
        return view('chit-schemes.index');
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->schemes->getForDataTable($request->only(['scheme_type', 'status'])))
            ->addColumn('scheme_type_label', fn (ChitScheme $scheme): string => str($scheme->scheme_type)->replace('_', ' ')->title()->toString())
            ->addColumn('amount_summary', fn (ChitScheme $scheme): string => $this->amountSummary($scheme))
            ->addColumn('status_badge', fn (ChitScheme $scheme): string => sprintf(
                '<span class="badge rounded-pill text-bg-%s">%s</span>',
                $scheme->status === 'active' ? 'success' : 'secondary',
                ucfirst($scheme->status)
            ))
            ->addColumn('actions', fn (ChitScheme $scheme): string => $this->actionButtons($scheme, $user))
            ->editColumn('created_at', fn (ChitScheme $scheme): string => optional($scheme->created_at)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('chit-schemes.create', [
            'scheme' => new ChitScheme([
                'scheme_type' => 'fixed_amount',
                'shop_bonus_type' => 'none',
                'late_fee_type' => 'none',
                'status' => 'active',
                'grace_period_days' => 0,
            ]),
        ]);
    }

    public function store(ChitSchemeStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $scheme = $this->schemeService->createScheme($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Scheme created successfully', $scheme, route('chit-schemes.show', $scheme));
    }

    public function show(ChitScheme $scheme): View
    {
        $scheme->loadCount([
            'enrollments',
            'enrollments as active_enrollments_count' => fn ($query) => $query->where('status', 'active'),
        ]);

        return view('chit-schemes.show', [
            'scheme' => $scheme,
            'totalPayable' => $this->schemeService->calculateTotalPayable($scheme),
        ]);
    }

    public function edit(ChitScheme $scheme): View
    {
        return view('chit-schemes.edit', [
            'scheme' => $scheme,
        ]);
    }

    public function update(ChitSchemeUpdateRequest $request, ChitScheme $scheme): JsonResponse|RedirectResponse
    {
        try {
            $scheme = $this->schemeService->updateScheme($scheme, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Scheme updated successfully', $scheme, route('chit-schemes.show', $scheme));
    }

    public function destroy(Request $request, ChitScheme $scheme): JsonResponse|RedirectResponse
    {
        try {
            $this->schemeService->deleteScheme($scheme);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Scheme deleted successfully', null, route('chit-schemes.index'));
    }

    public function changeStatus(Request $request, ChitScheme $scheme): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($request, ValidationException::withMessages($validator->errors()->toArray()));
        }

        $scheme = $this->schemeService->changeSchemeStatus($scheme, $validator->validated()['status']);

        return $this->successResponse($request, 'Scheme status updated successfully', $scheme, route('chit-schemes.index'));
    }

    private function amountSummary(ChitScheme $scheme): string
    {
        return match ($scheme->scheme_type) {
            'fixed_amount' => 'Rs. '.number_format((float) $scheme->monthly_amount, 2),
            'flexible_amount' => 'Rs. '.number_format((float) $scheme->min_amount, 2).' - Rs. '.number_format((float) $scheme->max_amount, 2),
            'gold_weight' => number_format((float) $scheme->gold_weight, 3).' g',
            default => '-',
        };
    }

    private function actionButtons(ChitScheme $scheme, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('schemes.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('chit-schemes.show', $scheme).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($user?->can('schemes.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('chit-schemes.edit', $scheme).'" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        if ($user?->can('schemes.status')) {
            $nextStatus = $scheme->status === 'active' ? 'inactive' : 'active';
            $icon = $scheme->status === 'active' ? 'bi-toggle-on' : 'bi-toggle-off';
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" data-scheme-action="status" data-status="'.$nextStatus.'" data-url="'.route('chit-schemes.status', $scheme).'" data-method="PATCH" title="Mark '.ucfirst($nextStatus).'"><i class="bi '.$icon.'"></i></button>';
        }

        if ((int) $scheme->active_enrollments_count === 0 && $user?->can('schemes.delete')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-scheme-action="delete" data-url="'.route('chit-schemes.destroy', $scheme).'" data-method="DELETE" title="Delete"><i class="bi bi-trash"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1">'.implode('', $buttons).'</div>';
    }

    private function successResponse(Request $request, string $message, ?ChitScheme $scheme = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'scheme' => $scheme ? new ChitSchemeResource($scheme) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('chit-schemes.index'))->with('success', $message);
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
