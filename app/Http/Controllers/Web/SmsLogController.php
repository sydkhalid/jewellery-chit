<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\SmsLogResource;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SmsLogController extends Controller
{
    public function __construct(
        private readonly MessageService $messageService
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->data($request);
        }

        return view('messages.sms-logs', $this->pageData());
    }

    public function retry(Request $request, SmsLog $log): JsonResponse
    {
        try {
            $result = $this->messageService->retrySmsLog($log);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'SMS message retried successfully',
            'data' => [
                'log' => new SmsLogResource($result['log']),
            ],
        ]);
    }

    private function data(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->messageService->smsLogsQuery($request->only([
            'customer_id',
            'mobile',
            'status',
            'message_type',
            'from_date',
            'to_date',
        ])))
            ->addColumn('customer_name', fn (SmsLog $log): string => $log->customer?->name ?? '-')
            ->addColumn('message_type_label', fn (SmsLog $log): string => str($log->message_type)->replace('_', ' ')->title()->toString())
            ->addColumn('channel', fn (): string => 'SMS')
            ->addColumn('message_preview', fn (SmsLog $log): string => e(str($log->message)->limit(80)->toString()))
            ->addColumn('status_badge', fn (SmsLog $log): string => $this->statusBadge($log->status))
            ->addColumn('actions', fn (SmsLog $log): string => $this->actions($log))
            ->editColumn('sent_at', fn (SmsLog $log): string => optional($log->sent_at)->format('d M Y h:i A') ?: '-')
            ->editColumn('created_at', fn (SmsLog $log): string => optional($log->created_at)->format('d M Y h:i A') ?: '-')
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(): array
    {
        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'messageTypes' => MessageService::MESSAGE_TYPES,
            'statuses' => MessageService::STATUSES,
        ];
    }

    private function actions(SmsLog $log): string
    {
        $buttons = [
            '<button type="button" class="btn btn-sm btn-light" data-message-response data-title="SMS response" data-response="'.e($log->response ?: 'No response stored.').'" title="View response"><i class="bi bi-eye"></i></button>',
        ];

        if ($log->status === 'failed' && auth()->user()?->can('messages.retry')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" data-message-retry data-table="sms" data-url="'.route('sms-logs.retry', $log).'" title="Retry"><i class="bi bi-arrow-clockwise"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1 justify-content-end">'.implode('', $buttons).'</div>';
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'sent' => 'success',
            'failed' => 'danger',
            default => 'secondary',
        };

        return sprintf('<span class="badge rounded-pill text-bg-%s">%s</span>', $class, ucfirst($status));
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
