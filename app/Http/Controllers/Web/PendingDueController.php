<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PendingDueFollowUpRequest;
use App\Http\Resources\PendingDueResource;
use App\Models\Branch;
use App\Models\ChitInstallment;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\User;
use App\Services\PendingDueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PendingDueController extends Controller
{
    public function __construct(
        private readonly PendingDueService $pendingDueService
    ) {
    }

    public function index(): View
    {
        return view('pending-dues.index', $this->pageData(null, 'All Pending Dues'));
    }

    public function today(): View
    {
        return view('pending-dues.today', $this->pageData('today', 'Today Dues'));
    }

    public function weekly(): View
    {
        return view('pending-dues.weekly', $this->pageData('weekly', 'Weekly Dues'));
    }

    public function monthly(): View
    {
        return view('pending-dues.monthly', $this->pageData('monthly', 'Monthly Dues'));
    }

    public function overdue(): View
    {
        return view('pending-dues.overdue', $this->pageData('overdue', 'Overdue Dues'));
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->pendingDueService->getPendingDuesQuery($request->only([
            'due_type',
            'customer_id',
            'staff_id',
            'branch_id',
            'scheme_id',
            'from_date',
            'to_date',
            'status',
            'followup_status',
        ])))
            ->addColumn('select_box', fn (ChitInstallment $installment): string => '<input type="checkbox" class="form-check-input" data-pending-due-select value="'.$installment->id.'">')
            ->addColumn('customer_code', fn (ChitInstallment $installment): string => $installment->enrollment?->customer?->customer_code ?? '-')
            ->addColumn('customer_name', fn (ChitInstallment $installment): string => $installment->enrollment?->customer?->name ?? '-')
            ->addColumn('mobile', fn (ChitInstallment $installment): string => $installment->enrollment?->customer?->mobile ?? '-')
            ->addColumn('chit_no', fn (ChitInstallment $installment): string => $installment->enrollment?->chit_no ?? '-')
            ->addColumn('scheme_name', fn (ChitInstallment $installment): string => $installment->enrollment?->scheme?->name ?? '-')
            ->addColumn('staff_name', fn (ChitInstallment $installment): string => $installment->enrollment?->assignedStaff?->name ?? '-')
            ->addColumn('branch_name', fn (ChitInstallment $installment): string => $installment->enrollment?->branch?->name ?? '-')
            ->addColumn('status_badge', fn (ChitInstallment $installment): string => $this->statusBadge($installment->status))
            ->addColumn('followup_badge', fn (ChitInstallment $installment): string => $this->followupBadge($installment->followup_status ?? 'pending'))
            ->addColumn('actions', fn (ChitInstallment $installment): string => $this->actionButtons($installment, $user))
            ->editColumn('due_date', fn (ChitInstallment $installment): string => optional($installment->due_date)->format('d M Y'))
            ->editColumn('promise_to_pay_date', fn (ChitInstallment $installment): string => optional($installment->promise_to_pay_date)->format('d M Y') ?: '-')
            ->rawColumns(['select_box', 'status_badge', 'followup_badge', 'actions'])
            ->toJson();
    }

    public function followup(PendingDueFollowUpRequest $request, ChitInstallment $installment): JsonResponse
    {
        $installment = $this->pendingDueService->updateFollowUpStatus($installment, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Follow-up updated successfully',
            'data' => [
                'installment' => new PendingDueResource($installment),
            ],
        ]);
    }

    public function sendReminder(Request $request, ChitInstallment $installment): JsonResponse
    {
        try {
            $result = $this->pendingDueService->sendDueReminder($installment, (string) $request->input('channel', 'whatsapp'));
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reminder sent successfully',
            'data' => $result,
        ]);
    }

    public function bulkReminder(Request $request): JsonResponse
    {
        $request->validate([
            'installment_ids' => ['required', 'array', 'min:1'],
            'installment_ids.*' => ['integer', 'exists:chit_installments,id'],
            'channel' => ['required', 'in:whatsapp,sms'],
        ]);

        try {
            $result = $this->pendingDueService->sendBulkDueReminder($request->input('installment_ids', []), (string) $request->input('channel'));
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => "{$result['count']} reminders sent successfully",
            'data' => $result,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(?string $dueType, string $title): array
    {
        return [
            'pageTitle' => $title,
            'selectedDueType' => $dueType,
            'summary' => $this->pendingDueService->calculateDueSummary(array_filter(['due_type' => $dueType])),
            'customers' => Customer::active()->orderBy('name')->get(),
            'staffUsers' => User::role(['Admin', 'Manager', 'Staff'])->active()->orderBy('name')->get(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'schemes' => ChitScheme::active()->orderBy('name')->get(),
            'statuses' => ['pending', 'partial', 'overdue'],
            'followupStatuses' => ['pending', 'called', 'promised', 'not_reachable', 'paid', 'closed'],
        ];
    }

    private function actionButtons(ChitInstallment $installment, mixed $user): string
    {
        $buttons = [];
        $customer = $installment->enrollment?->customer;
        $enrollment = $installment->enrollment;

        if ($customer && $user?->can('customers.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('customers.show', $customer).'" title="View customer"><i class="bi bi-person"></i></a>';
        }

        if ($enrollment && $user?->can('ledger.chit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('chit-enrollments.ledger', $enrollment).'" title="Chit ledger"><i class="bi bi-journal-text"></i></a>';
        }

        if ($user?->can('payments.create')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('payments.create', ['enrollment_id' => $installment->enrollment_id, 'installment_id' => $installment->id]).'" title="Collect payment"><i class="bi bi-cash-stack"></i></a>';
        }

        if ($user?->can('pending_dues.followup')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" data-pending-due-action="followup" data-url="'.route('pending-dues.followup', $installment).'" data-followup-status="'.e($installment->followup_status ?? 'pending').'" data-promise-date="'.optional($installment->promise_to_pay_date)->toDateString().'" data-remarks="'.e($installment->followup_remarks ?? '').'" title="Update follow-up"><i class="bi bi-telephone"></i></button>';
        }

        if ($user?->can('pending_dues.reminder')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-success" data-pending-due-action="reminder" data-url="'.route('pending-dues.reminder', $installment).'" title="Send reminder"><i class="bi bi-send"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'partial' => 'info',
            'overdue' => 'danger',
            default => 'secondary',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
    }

    private function followupBadge(string $status): string
    {
        $class = match ($status) {
            'called', 'promised' => 'info',
            'paid', 'closed' => 'success',
            'not_reachable' => 'warning',
            default => 'secondary',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst(str_replace('_', ' ', $status)));
    }

    private function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'data' => [
                'errors' => $exception->errors(),
            ],
        ], 422);
    }
}
