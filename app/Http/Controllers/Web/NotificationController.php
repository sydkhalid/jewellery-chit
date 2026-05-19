<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendMessageJob;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\SmsLogResource;
use App\Http\Resources\WhatsappLogResource;
use App\Models\Customer;
use App\Models\Notification;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class NotificationController extends Controller
{
    public function __construct(
        private readonly MessageService $messageService
    ) {
    }

    public function index(): View
    {
        return view('messages.index', $this->pageData() + [
            'summary' => [
                'notifications' => $this->messageService->notificationsQuery()->count(),
                'whatsapp' => $this->messageService->whatsappLogsQuery()->count(),
                'sms' => $this->messageService->smsLogsQuery()->count(),
                'failed' => $this->messageService->whatsappLogsQuery(['status' => 'failed'])->count()
                    + $this->messageService->smsLogsQuery(['status' => 'failed'])->count(),
            ],
        ]);
    }

    public function notifications(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->data($request);
        }

        return view('messages.notifications', $this->pageData());
    }

    public function sendWhatsapp(Request $request): JsonResponse
    {
        try {
            $data = $this->validatedMessageData($request);
            if ($this->shouldQueueMessages()) {
                SendMessageJob::dispatch('whatsapp', $data, $request->user()?->id)->onQueue('messages')->afterCommit();

                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp message queued successfully',
                    'data' => [
                        'queued' => true,
                    ],
                ], 202);
            }

            $result = $this->messageService->sendWhatsapp($data);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp message queued successfully',
            'data' => [
                'log' => new WhatsappLogResource($result['log']),
                'notification' => new NotificationResource($result['notification']),
            ],
        ]);
    }

    public function sendSms(Request $request): JsonResponse
    {
        try {
            $data = $this->validatedMessageData($request);
            if ($this->shouldQueueMessages()) {
                SendMessageJob::dispatch('sms', $data, $request->user()?->id)->onQueue('messages')->afterCommit();

                return response()->json([
                    'success' => true,
                    'message' => 'SMS message queued successfully',
                    'data' => [
                        'queued' => true,
                    ],
                ], 202);
            }

            $result = $this->messageService->sendSms($data);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'SMS message queued successfully',
            'data' => [
                'log' => new SmsLogResource($result['log']),
                'notification' => new NotificationResource($result['notification']),
            ],
        ]);
    }

    private function data(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->messageService->notificationsQuery($request->only([
            'customer_id',
            'mobile',
            'status',
            'message_type',
            'channel',
            'from_date',
            'to_date',
        ])))
            ->addColumn('customer_name', fn (Notification $notification): string => $notification->customer?->name ?? '-')
            ->addColumn('chit_no', fn (Notification $notification): string => $notification->enrollment?->chit_no ?? '-')
            ->addColumn('message_type_label', fn (Notification $notification): string => str($notification->notification_type)->replace('_', ' ')->title()->toString())
            ->addColumn('message_preview', fn (Notification $notification): string => e(str($notification->message)->limit(90)->toString()))
            ->addColumn('status_badge', fn (Notification $notification): string => $this->statusBadge($notification->status))
            ->addColumn('actions', fn (Notification $notification): string => '<button type="button" class="btn btn-sm btn-light" data-message-response data-title="Notification message" data-response="'.e($notification->message).'" title="View message"><i class="bi bi-eye"></i></button>')
            ->editColumn('sent_at', fn (Notification $notification): string => optional($notification->sent_at)->format('d M Y h:i A') ?: '-')
            ->editColumn('created_at', fn (Notification $notification): string => optional($notification->created_at)->format('d M Y h:i A') ?: '-')
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
            'channels' => ['whatsapp', 'sms', 'email', 'system'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedMessageData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'enrollment_id' => ['nullable', 'exists:chit_enrollments,id'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'message_type' => ['required', 'in:'.implode(',', MessageService::MESSAGE_TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);
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

    private function shouldQueueMessages(): bool
    {
        return config('queue.default') !== 'sync';
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
