<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiptCancelRequest;
use App\Http\Resources\ChitReceiptResource;
use App\Jobs\SendReceiptWhatsappJob;
use App\Models\Branch;
use App\Models\ChitEnrollment;
use App\Models\ChitReceipt;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\User;
use App\Repositories\ReceiptRepository;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly ReceiptRepository $receipts,
        private readonly ReceiptService $receiptService
    ) {
    }

    public function index(): View
    {
        return view('receipts.index', $this->filterOptions());
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->receipts->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'payment_mode_id',
            'staff_id',
            'branch_id',
            'from_date',
            'to_date',
            'status',
        ])))
            ->addColumn('customer_name', fn (ChitReceipt $receipt): string => $receipt->customer?->name ?? '-')
            ->addColumn('chit_no', fn (ChitReceipt $receipt): string => $receipt->enrollment?->chit_no ?? '-')
            ->addColumn('scheme_name', fn (ChitReceipt $receipt): string => $receipt->enrollment?->scheme?->name ?? '-')
            ->addColumn('payment_no', fn (ChitReceipt $receipt): string => $receipt->payment?->payment_no ?? '-')
            ->addColumn('payment_mode_name', fn (ChitReceipt $receipt): string => $receipt->payment?->paymentMode?->name ?? '-')
            ->addColumn('staff_name', fn (ChitReceipt $receipt): string => $receipt->payment?->staff?->name ?? '-')
            ->addColumn('branch_name', fn (ChitReceipt $receipt): string => $receipt->payment?->branch?->name ?? '-')
            ->addColumn('status_badge', fn (ChitReceipt $receipt): string => $this->statusBadge($receipt->status))
            ->addColumn('actions', fn (ChitReceipt $receipt): string => $this->actionButtons($receipt, $user))
            ->editColumn('receipt_date', fn (ChitReceipt $receipt): string => optional($receipt->receipt_date)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function show(ChitReceipt $receipt): View
    {
        $receipt->load($this->relations());

        return view('receipts.show', [
            'receipt' => $receipt,
        ]);
    }

    public function printThermal(ChitReceipt $receipt): View|RedirectResponse
    {
        try {
            $data = $this->receiptService->getThermalPrintData($receipt);
            $this->receiptService->incrementPrintCount($receipt);
        } catch (ValidationException $exception) {
            return redirect()->route('receipts.show', $receipt)->with('error', $this->firstError($exception));
        }

        return view('receipts.thermal-print', $data);
    }

    public function printA4(ChitReceipt $receipt): View|RedirectResponse
    {
        try {
            $data = $this->receiptService->getA4PrintData($receipt);
            $this->receiptService->incrementPrintCount($receipt);
        } catch (ValidationException $exception) {
            return redirect()->route('receipts.show', $receipt)->with('error', $this->firstError($exception));
        }

        return view('receipts.a4-print', $data);
    }

    public function downloadPdf(ChitReceipt $receipt): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = $this->receiptService->generatePdf($receipt);
        } catch (ValidationException $exception) {
            return redirect()->route('receipts.show', $receipt)->with('error', $this->firstError($exception));
        }

        return response()->download(Storage::disk('public')->path($path), $receipt->receipt_no.'.pdf');
    }

    public function duplicate(ChitReceipt $receipt): View|RedirectResponse
    {
        try {
            $data = $this->receiptService->duplicateReceipt($receipt);
        } catch (ValidationException $exception) {
            return redirect()->route('receipts.show', $receipt)->with('error', $this->firstError($exception));
        }

        return view('receipts.duplicate', $data);
    }

    public function cancel(ReceiptCancelRequest $request, ChitReceipt $receipt): JsonResponse|RedirectResponse
    {
        try {
            $receipt = $this->receiptService->cancelReceipt($receipt, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Receipt cancelled successfully', $receipt, route('receipts.show', $receipt));
    }

    public function whatsapp(Request $request, ChitReceipt $receipt): JsonResponse|RedirectResponse
    {
        if ($this->shouldQueueReceiptMessages()) {
            SendReceiptWhatsappJob::dispatch($receipt->id, $request->user()?->id)->onQueue('messages')->afterCommit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp receipt share queued successfully',
                    'data' => [
                        'queued' => true,
                        'receipt_id' => $receipt->id,
                    ],
                ], 202);
            }

            return back()->with('success', 'WhatsApp receipt share queued successfully');
        }

        try {
            $share = $this->receiptService->sendWhatsappReceipt($receipt);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp receipt share queued successfully',
                'data' => [
                    'share' => $share,
                ],
            ]);
        }

        return back()->with('success', 'WhatsApp receipt share queued successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(): array
    {
        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'enrollments' => ChitEnrollment::query()->with('customer')->orderByDesc('id')->get(),
            'paymentModes' => PaymentMode::active()->orderBy('name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'staffUsers' => User::role(['Admin', 'Manager', 'Staff'])->active()->orderBy('name')->get(),
        ];
    }

    private function actionButtons(ChitReceipt $receipt, mixed $user): string
    {
        $buttons = [];
        $isActive = $receipt->status === 'active';

        if ($user?->can('receipts.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('receipts.show', $receipt).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($isActive && $user?->can('receipts.print')) {
            $buttons[] = '<a class="btn btn-sm btn-light" target="_blank" href="'.route('receipts.thermal-print', $receipt).'" title="Thermal print"><i class="bi bi-printer"></i></a>';
            $buttons[] = '<a class="btn btn-sm btn-light" target="_blank" href="'.route('receipts.a4-print', $receipt).'" title="A4 print"><i class="bi bi-file-earmark-text"></i></a>';
        }

        if ($isActive && $user?->can('receipts.pdf')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('receipts.pdf', $receipt).'" title="Download PDF"><i class="bi bi-filetype-pdf"></i></a>';
        }

        if ($isActive && $user?->can('receipts.duplicate')) {
            $buttons[] = '<a class="btn btn-sm btn-light" target="_blank" href="'.route('receipts.duplicate', $receipt).'" title="Duplicate"><i class="bi bi-copy"></i></a>';
        }

        if ($isActive && $user?->can('receipts.whatsapp')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-success" data-receipt-action="whatsapp" data-url="'.route('receipts.whatsapp', $receipt).'" title="WhatsApp"><i class="bi bi-whatsapp"></i></button>';
        }

        if ($isActive && $user?->can('receipts.cancel')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-receipt-action="cancel" data-url="'.route('receipts.cancel', $receipt).'" title="Cancel"><i class="bi bi-x-circle"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        $class = $status === 'active' ? 'success' : 'danger';

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'customer',
            'enrollment.scheme',
            'payment.paymentMode',
            'payment.staff',
            'payment.branch',
            'payment.allocations.installment',
        ];
    }

    private function shouldQueueReceiptMessages(): bool
    {
        return config('queue.default') !== 'sync';
    }

    private function successResponse(Request $request, string $message, ChitReceipt $receipt, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'receipt' => new ChitReceiptResource($receipt->loadMissing($this->relations())),
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('receipts.index'))->with('success', $message);
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

    private function firstError(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first() ?? 'Unable to process receipt.';
    }
}
