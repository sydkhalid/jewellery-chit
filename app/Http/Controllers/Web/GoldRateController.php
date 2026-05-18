<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoldRateStoreRequest;
use App\Http\Requests\GoldRateUpdateRequest;
use App\Http\Resources\GoldRateResource;
use App\Models\GoldRate;
use App\Repositories\GoldRateRepository;
use App\Services\GoldRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class GoldRateController extends Controller
{
    public function __construct(
        private readonly GoldRateRepository $goldRates,
        private readonly GoldRateService $goldRateService
    ) {
    }

    public function index(): View
    {
        return view('gold-rates.index', [
            'statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->goldRates->getForDataTable($request->only([
            'rate_date',
            'status',
            'rate_locked',
            'from_date',
            'to_date',
        ])))
            ->addColumn('status_badge', fn (GoldRate $goldRate): string => $this->statusBadge($goldRate->status))
            ->addColumn('lock_badge', fn (GoldRate $goldRate): string => $goldRate->rate_locked
                ? '<span class="badge text-bg-dark">Locked</span>'
                : '<span class="badge text-bg-light">Open</span>')
            ->addColumn('creator_name', fn (GoldRate $goldRate): string => $goldRate->creator?->name ?? '-')
            ->addColumn('approver_name', fn (GoldRate $goldRate): string => $goldRate->approver?->name ?? '-')
            ->addColumn('actions', fn (GoldRate $goldRate): string => $this->actionButtons($goldRate, $user))
            ->editColumn('rate_date', fn (GoldRate $goldRate): string => optional($goldRate->rate_date)->format('d M Y'))
            ->rawColumns(['status_badge', 'lock_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('gold-rates.create', [
            'goldRate' => new GoldRate([
                'rate_date' => today(),
                'status' => 'pending',
                'rate_locked' => false,
            ]),
        ]);
    }

    public function store(GoldRateStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $goldRate = $this->goldRateService->createRate($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Gold rate created successfully', $goldRate, route('gold-rates.show', $goldRate));
    }

    public function show(GoldRate $goldRate): View
    {
        return view('gold-rates.show', [
            'goldRate' => $goldRate->load(['creator', 'approver']),
        ]);
    }

    public function edit(GoldRate $goldRate): View
    {
        return view('gold-rates.edit', [
            'goldRate' => $goldRate,
        ]);
    }

    public function update(GoldRateUpdateRequest $request, GoldRate $goldRate): JsonResponse|RedirectResponse
    {
        try {
            $goldRate = $this->goldRateService->updateRate($goldRate, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Gold rate updated successfully', $goldRate, route('gold-rates.show', $goldRate));
    }

    public function approve(Request $request, GoldRate $goldRate): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('gold_rates.approve'), 403);

        try {
            $goldRate = $this->goldRateService->approveRate($goldRate);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Gold rate approved successfully', $goldRate);
    }

    public function reject(Request $request, GoldRate $goldRate): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('gold_rates.approve'), 403);
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $goldRate = $this->goldRateService->rejectRate($goldRate, $data);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Gold rate rejected successfully', $goldRate);
    }

    public function lock(Request $request, GoldRate $goldRate): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('gold_rates.lock'), 403);

        try {
            $goldRate = $this->goldRateService->lockRate($goldRate);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Gold rate locked successfully', $goldRate);
    }

    public function latest(): JsonResponse
    {
        $rate = $this->goldRateService->getLatestApprovedRate();

        return response()->json([
            'success' => true,
            'message' => $rate ? 'Latest approved gold rate fetched successfully' : 'No approved gold rate found',
            'data' => [
                'rate' => $rate ? new GoldRateResource($rate->load(['creator', 'approver'])) : null,
            ],
        ]);
    }

    private function actionButtons(GoldRate $goldRate, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('gold_rates.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('gold-rates.show', $goldRate).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if (! $goldRate->rate_locked && $user?->can('gold_rates.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('gold-rates.edit', $goldRate).'" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        if (! $goldRate->rate_locked && $goldRate->status !== 'approved' && $user?->can('gold_rates.approve')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-success" data-gold-rate-action="approve" data-url="'.route('gold-rates.approve', $goldRate).'" title="Approve"><i class="bi bi-check2-circle"></i></button>';
        }

        if (! $goldRate->rate_locked && $goldRate->status !== 'rejected' && $user?->can('gold_rates.approve')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" data-gold-rate-action="reject" data-url="'.route('gold-rates.reject', $goldRate).'" title="Reject"><i class="bi bi-x-octagon"></i></button>';
        }

        if (! $goldRate->rate_locked && $goldRate->status === 'approved' && $user?->can('gold_rates.lock')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-dark" data-gold-rate-action="lock" data-url="'.route('gold-rates.lock', $goldRate).'" title="Lock"><i class="bi bi-lock"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
    }

    private function successResponse(Request $request, string $message, ?GoldRate $goldRate = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'gold_rate' => $goldRate ? new GoldRateResource($goldRate->loadMissing(['creator', 'approver'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('gold-rates.index'))->with('success', $message);
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
