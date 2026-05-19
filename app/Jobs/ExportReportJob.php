<?php

namespace App\Jobs;

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
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly string $type,
        private readonly string $format,
        private readonly array $filters,
        private readonly string $path,
        private readonly ?int $actorId = null
    ) {
    }

    public function handle(ReportService $reports): void
    {
        if ($this->actorId) {
            Auth::loginUsingId($this->actorId);
        }

        $payload = $reports->exportPayload($this->type, $this->filters);

        if ($this->format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf-template', [
                'payload' => $payload,
                'type' => $this->type,
            ])->setPaper('a4', 'landscape');

            Storage::disk('public')->put($this->path, $pdf->output());
        } else {
            $class = $this->exportClass($this->type);
            Excel::store(new $class($payload), $this->path, 'public');
        }

        $reports->logReportAction($this->type, 'queued_'.$this->format.'_export');
        Auth::logout();
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
}
