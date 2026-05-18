<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\NotificationResource;
use App\Http\Resources\SmsLogResource;
use App\Http\Resources\WhatsappLogResource;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageController extends BaseApiController
{
    public function __construct(
        private readonly MessageService $messageService
    ) {
    }

    public function whatsapp(Request $request): JsonResponse
    {
        try {
            $data = $this->validatedMessageData($request);
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

    public function sms(Request $request): JsonResponse
    {
        try {
            $data = $this->validatedMessageData($request);
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

    public function notifications(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $notifications = $this->messageService
            ->notificationsQuery($request->only(['customer_id', 'mobile', 'status', 'message_type', 'channel', 'from_date', 'to_date']))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Notifications fetched successfully',
            'data' => [
                'notifications' => NotificationResource::collection($notifications->getCollection()),
                'pagination' => $this->pagination($notifications),
            ],
        ]);
    }

    public function whatsappLogs(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $logs = $this->messageService
            ->whatsappLogsQuery($request->only(['customer_id', 'mobile', 'status', 'message_type', 'from_date', 'to_date']))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp logs fetched successfully',
            'data' => [
                'logs' => WhatsappLogResource::collection($logs->getCollection()),
                'pagination' => $this->pagination($logs),
            ],
        ]);
    }

    public function smsLogs(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $logs = $this->messageService
            ->smsLogsQuery($request->only(['customer_id', 'mobile', 'status', 'message_type', 'from_date', 'to_date']))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'SMS logs fetched successfully',
            'data' => [
                'logs' => SmsLogResource::collection($logs->getCollection()),
                'pagination' => $this->pagination($logs),
            ],
        ]);
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

    /**
     * @return array<string, int>
     */
    private function pagination(mixed $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
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
