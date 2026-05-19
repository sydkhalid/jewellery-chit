<?php

namespace App\Http\Controllers\Web;

use App\Exports\ActiveChitReportExport;
use App\Exports\BranchReportExport;
use App\Exports\CancelledChitReportExport;
use App\Exports\CashflowReportExport;
use App\Exports\ClosedReportExport;
use App\Exports\CollectionReportExport;
use App\Exports\CustomerReportExport;
use App\Exports\MaturedReportExport;
use App\Exports\OverdueReportExport;
use App\Exports\PendingReportExport;
use App\Exports\ReceiptReportExport;
use App\Exports\SchemeReportExport;
use App\Exports\StaffReportExport;
use App\Http\Controllers\Controller;
use App\Jobs\ExportReportJob;
use App\Models\Branch;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports
    ) {
    }

    public function index(): View
    {
        return view('reports.index', [
            'definitions' => $this->reports->definitions(),
        ]);
    }

    public function customers(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'customers');
    }

    public function activeChits(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'active-chits');
    }

    public function collections(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'collections');
    }

    public function pending(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'pending');
    }

    public function overdue(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'overdue');
    }

    public function matured(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'matured');
    }

    public function closed(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'closed');
    }

    public function cancelled(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'cancelled');
    }

    public function staff(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'staff');
    }

    public function branches(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'branches');
    }

    public function schemes(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'schemes');
    }

    public function receipts(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'receipts');
    }

    public function cashflow(Request $request): View|JsonResponse
    {
        return $this->reportPage($request, 'cashflow');
    }

    public function exportExcel(Request $request, string $type): BinaryFileResponse|JsonResponse
    {
        abort_unless($request->user()?->can('reports.export_excel'), 403);

        if ($request->boolean('queue')) {
            return $this->queueExport($request, $type, 'xlsx');
        }

        $payload = $this->reports->exportPayload($type, $this->filters($request));
        $class = $this->exportClass($type);
        $this->reports->logReportAction($type, 'export_excel');

        return Excel::download(new $class($payload), $this->fileName($type, 'xlsx'));
    }

    public function exportPdf(Request $request, string $type): \Illuminate\Http\Response|JsonResponse
    {
        abort_unless($request->user()?->can('reports.export_pdf'), 403);

        if ($request->boolean('queue')) {
            return $this->queueExport($request, $type, 'pdf');
        }

        $payload = $this->reports->exportPayload($type, $this->filters($request));
        $this->reports->logReportAction($type, 'export_pdf');

        return Pdf::loadView('reports.pdf-template', [
            'payload' => $payload,
            'type' => $type,
        ])->setPaper('a4', 'landscape')->download($this->fileName($type, 'pdf'));
    }

    public function printReport(Request $request, string $type): View
    {
        abort_unless($request->user()?->can('reports.print'), 403);

        $payload = $this->reports->exportPayload($type, $this->filters($request));
        $this->reports->logReportAction($type, 'print');

        return view('reports.print-template', [
            'payload' => $payload,
            'type' => $type,
        ]);
    }

    private function reportPage(Request $request, string $type): View|JsonResponse
    {
        if ($request->boolean('summary')) {
            return response()->json([
                'success' => true,
                'message' => 'Report summary fetched successfully',
                'data' => [
                    'summary' => $this->reports->summary($type, $this->filters($request)),
                ],
            ]);
        }

        if ($request->ajax()) {
            return DataTables::of($this->reports->rows($type, $this->filters($request)))->toJson();
        }

        $definition = $this->reports->definition($type);
        $this->reports->logReportAction($type, 'view');

        return view("reports.{$type}", $this->formOptions() + [
            'type' => $type,
            'definition' => $definition,
            'summary' => $this->reports->summary($type, $this->filters($request)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->only([
            'from_date',
            'to_date',
            'branch_id',
            'staff_id',
            'scheme_id',
            'customer_id',
            'status',
            'payment_mode_id',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'branches' => Branch::active()->orderBy('name')->get(),
            'staffUsers' => User::role(['Admin', 'Manager', 'Staff'])->active()->orderBy('name')->get(),
            'schemes' => ChitScheme::orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'paymentModes' => PaymentMode::active()->orderBy('name')->get(),
            'statuses' => ['active', 'inactive', 'pending', 'partial', 'overdue', 'paid', 'matured', 'closed', 'cancelled', 'success'],
        ];
    }

    /**
     * @return class-string
     */
    private function exportClass(string $type): string
    {
        return match ($type) {
            'customers' => CustomerReportExport::class,
            'active-chits' => ActiveChitReportExport::class,
            'collections' => CollectionReportExport::class,
            'pending' => PendingReportExport::class,
            'overdue' => OverdueReportExport::class,
            'matured' => MaturedReportExport::class,
            'closed' => ClosedReportExport::class,
            'cancelled' => CancelledChitReportExport::class,
            'staff' => StaffReportExport::class,
            'branches' => BranchReportExport::class,
            'schemes' => SchemeReportExport::class,
            'receipts' => ReceiptReportExport::class,
            'cashflow' => CashflowReportExport::class,
            default => abort(404),
        };
    }

    private function fileName(string $type, string $extension): string
    {
        return str($type)->replace('-', '_')->toString().'_report_'.now()->format('Ymd_His').".{$extension}";
    }

    private function queueExport(Request $request, string $type, string $format): JsonResponse
    {
        $fileName = $this->fileName($type, $format);
        $path = 'report-exports/'.$fileName;

        ExportReportJob::dispatch($type, $format, $this->filters($request), $path, $request->user()?->id)
            ->onQueue('exports')
            ->afterCommit();

        return response()->json([
            'success' => true,
            'message' => strtoupper($format).' report export queued successfully',
            'data' => [
                'queued' => true,
                'path' => $path,
            ],
        ], 202);
    }
}
