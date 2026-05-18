<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsappLogResource;
use App\Models\Customer;
use App\Models\WhatsappLog;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class WhatsappLogController extends Controller
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

        return view('messages.whatsapp-logs', $this->pageData());
    }

    public function retry(Request $request, WhatsappLog $log): JsonResponse
    {
        try {
            $result = $this->messageService->retryWhatsappLog($log);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp message retried successfully',
            'data' => [
                'log' => new WhatsappLogResource($result['log']),
            ],
        ]);
    }

    private function data(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->messageService->whatsappLogsQuery($request->only([
            'customer_id',
            'mobile',
            'status',
            'message_type',
            'from_date',
            'to_date',
        ])))
            ->addColumn('customer_name', fn (WhatsappLog $log): string => $log->customer?->name ?? '-')
            ->addColumn('message_type_label', fn (WhatsappLog $log): string => str($log->message_type)->replace('_', ' ')->title()->toString())
            ->addColumn('channel', fn (): string => 'WhatsApp')
            ->addColumn('message_preview', fn (WhatsappLog $log): string => e(str($log->message)->limit(80)->toString()))
            ->addColumn('status_badge', fn (WhatsappLog $log): string => $this->statusBadge($log->status))
            ->addColumn('actions', fn (WhatsappLog $log): string => $this->actions($log))
            ->editColumn('sent_at', fn (WhatsappLog $log): string => optional($log->sent_at)->format('d M Y h:i A') ?: '-')
            ->editColumn('created_at', fn (WhatsappLog $log): string => optional($log->created_at)->format('d M Y h:i A') ?: '-')
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

    private function actions(WhatsappLog $log): string
    {
        $buttons = [
            '<button type="button" class="btn btn-sm btn-light" data-message-response data-title="WhatsApp response" data-response="'.e($log->response ?: 'No response stored.').'" title="View response"><i class="bi bi-eye"></i></button>',
        ];

        if ($log->status === 'failed' && auth()->user()?->can('messages.retry')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" data-message-retry data-table="whatsapp" data-url="'.route('whatsapp-logs.retry', $log).'" title="Retry"><i class="bi bi-arrow-clockwise"></i></button>';
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
