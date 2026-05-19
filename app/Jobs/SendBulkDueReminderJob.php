<?php

namespace App\Jobs;

use App\Services\PendingDueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class SendBulkDueReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<int, int>  $installmentIds
     */
    public function __construct(
        private readonly array $installmentIds,
        private readonly string $channel,
        private readonly ?int $actorId = null
    ) {
    }

    public function handle(PendingDueService $pendingDues): void
    {
        if ($this->actorId) {
            Auth::loginUsingId($this->actorId);
        }

        $pendingDues->sendBulkDueReminder($this->installmentIds, $this->channel);

        Auth::logout();
    }
}
