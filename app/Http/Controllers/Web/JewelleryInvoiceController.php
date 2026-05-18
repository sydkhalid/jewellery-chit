<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\JewelleryInvoiceCancelRequest;
use App\Http\Requests\JewelleryInvoiceStoreRequest;
use App\Http\Requests\JewelleryInvoiceUpdateRequest;
use App\Http\Resources\JewelleryInvoiceResource;
use App\Models\ChitEnrollment;
use App\Models\Customer;
use App\Models\JewelleryInvoice;
use App\Repositories\JewelleryInvoiceRepository;
use App\Services\GoldRateService;
use App\Services\JewelleryInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class JewelleryInvoiceController extends Controller
{
    public function __construct(
        private readonly JewelleryInvoiceRepository $invoices,
        private readonly JewelleryInvoiceService $jewelleryInvoiceService,
        private readonly GoldRateService $goldRateService
    ) {
    }

    public function index(): View
    {
        return view('jewellery-invoices.index', $this->formOptions());
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->invoices->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'status',
            'from_date',
            'to_date',
        ])))
            ->addColumn('customer_name', fn (JewelleryInvoice $invoice): string => $invoice->customer?->name ?? '-')
            ->addColumn('chit_no', fn (JewelleryInvoice $invoice): string => $invoice->enrollment?->chit_no ?? '-')
            ->addColumn('scheme_name', fn (JewelleryInvoice $invoice): string => $invoice->enrollment?->scheme?->name ?? '-')
            ->addColumn('status_badge', fn (JewelleryInvoice $invoice): string => $this->statusBadge($invoice->status))
            ->addColumn('actions', fn (JewelleryInvoice $invoice): string => $this->actionButtons($invoice, $user))
            ->editColumn('invoice_date', fn (JewelleryInvoice $invoice): string => optional($invoice->invoice_date)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('jewellery-invoices.create', $this->formOptions() + [
            'invoice' => new JewelleryInvoice([
                'invoice_date' => now(),
                'status' => 'draft',
                'gold_rate' => $this->goldRateService->getLatestApprovedRate()?->gold_22k ?? 1,
            ]),
        ]);
    }

    public function store(JewelleryInvoiceStoreRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $invoice = $this->jewelleryInvoiceService->createInvoice($request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Jewellery invoice created successfully', $invoice, route('jewellery-invoices.show', $invoice));
    }

    public function show(JewelleryInvoice $invoice): View
    {
        $invoice->load(['customer', 'enrollment.scheme', 'items', 'creator', 'finalizer', 'canceller']);

        return view('jewellery-invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(JewelleryInvoice $invoice): View
    {
        $invoice->load(['customer', 'enrollment.scheme', 'items']);

        return view('jewellery-invoices.edit', $this->formOptions() + [
            'invoice' => $invoice,
        ]);
    }

    public function update(JewelleryInvoiceUpdateRequest $request, JewelleryInvoice $invoice): JsonResponse|RedirectResponse
    {
        try {
            $invoice = $this->jewelleryInvoiceService->updateInvoice($invoice, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Jewellery invoice updated successfully', $invoice, route('jewellery-invoices.show', $invoice));
    }

    public function finalize(Request $request, JewelleryInvoice $invoice): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('jewellery.create'), 403);
        abort_if((float) $invoice->chit_adjustment_amount > 0 && ! ($request->user()?->can('jewellery.adjust_chit') ?? false), 403);

        try {
            $invoice = $this->jewelleryInvoiceService->finalizeInvoice($invoice);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Jewellery invoice finalized successfully', $invoice, route('jewellery-invoices.show', $invoice));
    }

    public function cancel(JewelleryInvoiceCancelRequest $request, JewelleryInvoice $invoice): JsonResponse|RedirectResponse
    {
        try {
            $invoice = $this->jewelleryInvoiceService->cancelInvoice($invoice, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Jewellery invoice cancelled successfully', $invoice, route('jewellery-invoices.show', $invoice));
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'discount' => ['nullable', 'numeric', 'min:0'],
            'chit_adjustment_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.net_weight' => ['required', 'numeric', 'min:0'],
            'items.*.gross_weight' => ['required', 'numeric', 'min:0'],
            'items.*.rate' => ['required', 'numeric', 'min:1'],
            'items.*.making_charge' => ['nullable', 'numeric', 'min:0'],
            'items.*.wastage' => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $totals = $this->jewelleryInvoiceService->calculateInvoiceTotals($validated['items'], $validated);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $exception->errors(),
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice totals calculated successfully',
            'data' => [
                'totals' => $totals,
            ],
        ]);
    }

    public function getCustomerMaturedChits(Customer $customer): JsonResponse
    {
        $chits = $this->jewelleryInvoiceService->getCustomerMaturedChits($customer->id)
            ->map(function (ChitEnrollment $enrollment): array {
                $availability = $this->jewelleryInvoiceService->adjustmentAvailability($enrollment);

                return [
                    'id' => $enrollment->id,
                    'chit_no' => $enrollment->chit_no,
                    'scheme_name' => $enrollment->scheme?->name,
                    'status' => $enrollment->status,
                    'maturity_date' => optional($enrollment->maturity_date)->toDateString(),
                    'final_maturity_value' => $availability['final_maturity_value'],
                    'used_adjustment' => $availability['used_adjustment'],
                    'available_adjustment' => $availability['available_adjustment'],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Matured chits fetched successfully',
            'data' => [
                'chits' => $chits,
            ],
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
                ->with(['customer', 'scheme', 'closure'])
                ->orderByDesc('id')
                ->get()
                ->filter(fn (ChitEnrollment $enrollment): bool => $this->jewelleryInvoiceService->isAdjustmentEligible($enrollment))
                ->values(),
            'statuses' => ['draft', 'final', 'cancelled'],
            'latestGoldRate' => $this->goldRateService->getLatestApprovedRate(),
        ];
    }

    private function actionButtons(JewelleryInvoice $invoice, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('jewellery.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('jewellery-invoices.show', $invoice).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($invoice->status === 'draft' && $user?->can('jewellery.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('jewellery-invoices.edit', $invoice).'" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        if ($invoice->status === 'draft' && $user?->can('jewellery.create')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-success" data-jewellery-action="finalize" data-url="'.route('jewellery-invoices.finalize', $invoice).'" title="Finalize"><i class="bi bi-patch-check"></i></button>';
        }

        if (in_array($invoice->status, ['draft', 'final'], true) && $user?->can('jewellery.cancel')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-jewellery-action="cancel" data-url="'.route('jewellery-invoices.cancel', $invoice).'" title="Cancel"><i class="bi bi-x-circle"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'final' => 'success',
            'cancelled' => 'danger',
            default => 'warning',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
    }

    private function successResponse(Request $request, string $message, ?JewelleryInvoice $invoice = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'invoice' => $invoice ? new JewelleryInvoiceResource($invoice->loadMissing(['customer', 'enrollment.scheme', 'items', 'creator', 'finalizer'])) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('jewellery-invoices.index'))->with('success', $message);
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
