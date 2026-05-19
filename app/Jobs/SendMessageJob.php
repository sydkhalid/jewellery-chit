<?php

namespace App\Jobs;

use App\Services\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly string $channel,
        private readonly array $data,
        private readonly ?int $actorId = null
    ) {
    }

    public function handle(MessageService $messages): void
    {
        if ($this->actorId) {
            Auth::loginUsingId($this->actorId);
        }

        $this->channel === 'sms'
            ? $messages->sendSms($this->data)
            : $messages->sendWhatsapp($this->data);

        Auth::logout();
    }
}
