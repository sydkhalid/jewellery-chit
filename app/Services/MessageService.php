<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitReceipt;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\ShopSetting;
use App\Models\SmsLog;
use App\Models\WhatsappLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public const MESSAGE_TYPES = [
        'due_reminder',
        'receipt',
        'maturity',
        'birthday',
        'anniversary',
        'general',
    ];

    public const STATUSES = [
        'pending',
        'sent',
        'failed',
    ];

    public const CHANNELS = [
        'whatsapp',
        'sms',
        'email',
        'system',
    ];

    public const TEMPLATES = [
        'due_reminder' => 'Dear {customer_name}, your chit installment for {chit_no} amount ₹{amount} is due on {due_date}. Please pay before due date.',
        'receipt' => 'Dear {customer_name}, payment of ₹{amount} received for chit {chit_no}. Receipt No: {receipt_no}. Thank you.',
        'maturity' => 'Dear {customer_name}, your chit {chit_no} has reached maturity. Please contact the shop for closing process.',
        'birthday' => 'Dear {customer_name}, wishing you a very Happy Birthday from {shop_name}.',
        'anniversary' => 'Dear {customer_name}, wishing you a Happy Anniversary from {shop_name}.',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function whatsappLogsQuery(array $filters = []): Builder
    {
        return WhatsappLog::query()
            ->with('customer')
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->where('customer_id', $customerId))
            ->when(Arr::get($filters, 'mobile'), fn (Builder $query, string $mobile): Builder => $query->where('mobile', 'like', "%{$mobile}%"))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'message_type'), fn (Builder $query, string $type): Builder => $query->where('message_type', $type))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $from): Builder => $query->whereDate('created_at', '>=', $from))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $to): Builder => $query->whereDate('created_at', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function smsLogsQuery(array $filters = []): Builder
    {
        return SmsLog::query()
            ->with('customer')
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->where('customer_id', $customerId))
            ->when(Arr::get($filters, 'mobile'), fn (Builder $query, string $mobile): Builder => $query->where('mobile', 'like', "%{$mobile}%"))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'message_type'), fn (Builder $query, string $type): Builder => $query->where('message_type', $type))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $from): Builder => $query->whereDate('created_at', '>=', $from))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $to): Builder => $query->whereDate('created_at', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function notificationsQuery(array $filters = []): Builder
    {
        return Notification::query()
            ->with(['customer', 'enrollment'])
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->where('customer_id', $customerId))
            ->when(Arr::get($filters, 'mobile'), fn (Builder $query, string $mobile): Builder => $query->whereHas('customer', fn (Builder $customer): Builder => $customer->where('mobile', 'like', "%{$mobile}%")))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'message_type'), fn (Builder $query, string $type): Builder => $query->where('notification_type', $type))
            ->when(Arr::get($filters, 'channel'), fn (Builder $query, string $channel): Builder => $query->where('channel', $channel))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $from): Builder => $query->whereDate('created_at', '>=', $from))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $to): Builder => $query->whereDate('created_at', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sendWhatsapp(array $data): array
    {
        return $this->sendByChannel('whatsapp', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sendSms(array $data): array
    {
        return $this->sendByChannel('sms', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendDueReminder(Customer $customer, ChitEnrollment $enrollment, ChitInstallment $installment, string $channel): array
    {
        $message = $this->buildMessageFromTemplate(self::TEMPLATES['due_reminder'], [
            'customer_name' => $customer->name,
            'chit_no' => $enrollment->chit_no,
            'amount' => number_format((float) $installment->balance_amount, 2),
            'due_date' => optional($installment->due_date)->format('d M Y'),
        ]);

        return $this->sendByChannel($channel, [
            'customer_id' => $customer->id,
            'enrollment_id' => $enrollment->id,
            'mobile' => $customer->mobile,
            'message_type' => 'due_reminder',
            'title' => 'Due reminder',
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendReceiptMessage(ChitReceipt $receipt, string $channel): array
    {
        $receipt->loadMissing(['customer', 'enrollment']);
        $customer = $receipt->customer;
        $enrollment = $receipt->enrollment;

        if (! $customer || ! $enrollment) {
            throw ValidationException::withMessages(['receipt' => 'Receipt customer and chit details are required for messaging.']);
        }

        $message = $this->buildMessageFromTemplate(self::TEMPLATES['receipt'], [
            'customer_name' => $customer->name,
            'amount' => number_format((float) $receipt->amount, 2),
            'chit_no' => $enrollment->chit_no,
            'receipt_no' => $receipt->receipt_no,
        ]);

        return $this->sendByChannel($channel, [
            'customer_id' => $customer->id,
            'enrollment_id' => $enrollment->id,
            'mobile' => $customer->mobile,
            'message_type' => 'receipt',
            'title' => 'Receipt message',
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMaturityReminder(ChitEnrollment $enrollment, string $channel): array
    {
        $enrollment->loadMissing('customer');
        $customer = $enrollment->customer;

        if (! $customer) {
            throw ValidationException::withMessages(['customer' => 'Customer details are required for maturity reminder.']);
        }

        $message = $this->buildMessageFromTemplate(self::TEMPLATES['maturity'], [
            'customer_name' => $customer->name,
            'chit_no' => $enrollment->chit_no,
        ]);

        return $this->sendByChannel($channel, [
            'customer_id' => $customer->id,
            'enrollment_id' => $enrollment->id,
            'mobile' => $customer->mobile,
            'message_type' => 'maturity',
            'title' => 'Maturity reminder',
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendBirthdayWish(Customer $customer, string $channel): array
    {
        $message = $this->buildMessageFromTemplate(self::TEMPLATES['birthday'], [
            'customer_name' => $customer->name,
            'shop_name' => (string) ShopSetting::getByKey('shop_name', config('app.name')),
        ]);

        return $this->sendByChannel($channel, [
            'customer_id' => $customer->id,
            'mobile' => $customer->mobile,
            'message_type' => 'birthday',
            'title' => 'Birthday wish',
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendAnniversaryWish(Customer $customer, string $channel): array
    {
        $message = $this->buildMessageFromTemplate(self::TEMPLATES['anniversary'], [
            'customer_name' => $customer->name,
            'shop_name' => (string) ShopSetting::getByKey('shop_name', config('app.name')),
        ]);

        return $this->sendByChannel($channel, [
            'customer_id' => $customer->id,
            'mobile' => $customer->mobile,
            'message_type' => 'anniversary',
            'title' => 'Anniversary wish',
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function retryWhatsappLog(WhatsappLog $log): array
    {
        return $this->retryLog($log, 'whatsapp');
    }

    /**
     * @return array<string, mixed>
     */
    public function retrySmsLog(SmsLog $log): array
    {
        return $this->retryLog($log, 'sms');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createNotificationLog(array $data): Notification
    {
        $notification = Notification::create([
            'customer_id' => $data['customer_id'] ?? null,
            'enrollment_id' => $data['enrollment_id'] ?? null,
            'notification_type' => $this->normalizeMessageType((string) ($data['notification_type'] ?? $data['message_type'] ?? 'general')),
            'title' => $data['title'] ?? str((string) ($data['message_type'] ?? 'general'))->replace('_', ' ')->title()->toString(),
            'message' => $data['message'],
            'channel' => $this->normalizeChannel((string) ($data['channel'] ?? 'system'), true),
            'status' => $data['status'] ?? 'pending',
            'sent_at' => $data['sent_at'] ?? null,
        ]);

        $this->logMessageAction($notification, 'notifications', 'notification', 'notification log created', null, $notification->toArray());

        return $notification;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function buildMessageFromTemplate(string $template, array $variables): string
    {
        $replace = collect($variables)
            ->mapWithKeys(fn (mixed $value, string $key): array => ['{'.$key.'}' => (string) $value])
            ->all();

        return strtr($template, $replace);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sendByChannel(string $channel, array $data): array
    {
        $channel = $this->normalizeChannel($channel);
        $data = $this->normalizeMessageData($data, $channel);

        return DB::transaction(function () use ($channel, $data): array {
            $log = $channel === 'whatsapp'
                ? WhatsappLog::create($this->logPayload($data))
                : SmsLog::create($this->logPayload($data));

            $providerResponse = $this->placeholderProviderResponse($channel, $data);
            $status = $providerResponse['success'] ? 'sent' : 'failed';
            $oldValues = $log->toArray();

            $log->update([
                'status' => $status,
                'response' => json_encode($providerResponse),
                'sent_at' => $status === 'sent' ? now() : null,
            ]);

            $log = $log->refresh()->load('customer');
            $notification = $this->createNotificationLog($data + [
                'channel' => $channel,
                'status' => $status,
                'sent_at' => $status === 'sent' ? now() : null,
            ]);

            $this->logMessageAction(
                $log,
                $channel === 'whatsapp' ? 'whatsapp_logs' : 'sms_logs',
                $status === 'sent' ? 'message_send' : 'message_failure',
                "{$channel} message {$status}",
                $oldValues,
                $log->toArray()
            );

            return [
                'channel' => $channel,
                'status' => $status,
                'message' => $log->message,
                'log' => $log,
                'notification' => $notification->loadMissing(['customer', 'enrollment']),
                'provider_response' => $providerResponse,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function retryLog(WhatsappLog|SmsLog $log, string $channel): array
    {
        $channel = $this->normalizeChannel($channel);

        if ($log->status !== 'failed') {
            throw ValidationException::withMessages(['status' => 'Only failed messages can be retried.']);
        }

        return DB::transaction(function () use ($log, $channel): array {
            $oldValues = $log->toArray();
            $providerResponse = $this->placeholderProviderResponse($channel, [
                'mobile' => $log->mobile,
                'message' => $log->message,
            ]);
            $status = $providerResponse['success'] ? 'sent' : 'failed';

            $log->update([
                'retry_count' => (int) $log->retry_count + 1,
                'status' => $status,
                'response' => json_encode($providerResponse),
                'sent_at' => $status === 'sent' ? now() : $log->sent_at,
            ]);

            $log = $log->refresh()->load('customer');
            $this->logMessageAction(
                $log,
                $channel === 'whatsapp' ? 'whatsapp_logs' : 'sms_logs',
                $status === 'sent' ? 'message_retry' : 'message_failure',
                "{$channel} message retry {$status}",
                $oldValues,
                $log->toArray()
            );

            return [
                'channel' => $channel,
                'status' => $status,
                'message' => $log->message,
                'log' => $log,
                'provider_response' => $providerResponse,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeMessageData(array $data, string $channel): array
    {
        $customer = ! empty($data['customer_id']) ? Customer::find($data['customer_id']) : null;
        $mobile = trim((string) ($data['mobile'] ?? $customer?->mobile ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($mobile === '') {
            throw ValidationException::withMessages(['mobile' => 'Mobile number is required.']);
        }

        if ($message === '') {
            throw ValidationException::withMessages(['message' => 'Message is required.']);
        }

        return [
            'customer_id' => $customer?->id ?? (filled($data['customer_id'] ?? null) ? $data['customer_id'] : null),
            'enrollment_id' => filled($data['enrollment_id'] ?? null) ? $data['enrollment_id'] : null,
            'mobile' => $mobile,
            'message' => $message,
            'message_type' => $this->normalizeMessageType((string) ($data['message_type'] ?? 'general')),
            'title' => $data['title'] ?? str((string) ($data['message_type'] ?? 'general'))->replace('_', ' ')->title()->toString(),
            'channel' => $channel,
        ];
    }

    private function normalizeMessageType(string $type): string
    {
        if (! in_array($type, self::MESSAGE_TYPES, true)) {
            throw ValidationException::withMessages(['message_type' => 'Invalid message type selected.']);
        }

        return $type;
    }

    private function normalizeChannel(string $channel, bool $allowSystem = false): string
    {
        $allowed = $allowSystem ? self::CHANNELS : ['whatsapp', 'sms'];

        if (! in_array($channel, $allowed, true)) {
            throw ValidationException::withMessages(['channel' => 'Invalid message channel selected.']);
        }

        return $channel;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function logPayload(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'],
            'message_type' => $data['message_type'],
            'mobile' => $data['mobile'],
            'message' => $data['message'],
            'response' => null,
            'status' => 'pending',
            'retry_count' => 0,
            'sent_at' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function placeholderProviderResponse(string $channel, array $data): array
    {
        $enabled = (bool) ShopSetting::getByKey("{$channel}_enabled", false);
        $apiUrl = ShopSetting::getByKey("{$channel}_api_url");

        return [
            'success' => true,
            'provider' => 'placeholder',
            'channel' => $channel,
            'enabled' => $enabled,
            'api_url_configured' => filled($apiUrl),
            'mobile' => $data['mobile'],
            'message' => 'Placeholder '.$channel.' provider accepted the message.',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logMessageAction(
        Model $model,
        string $module,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => $module,
            'description' => ucfirst(str_replace('_', ' ', $module)).' '.$action.'.',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
