<?php

namespace App\Jobs;

use App\Models\ChitInstallment;
use App\Services\PendingDueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class SendDueReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $installmentId,
        private readonly string $channel,
        private readonly ?int $actorId = null
    ) {
    }

    public function handle(PendingDueService $pendingDues): void
    {
        if ($this->actorId) {
            Auth::loginUsingId($this->actorId);
        }

        $pendingDues->sendDueReminder(
            ChitInstallment::query()->findOrFail($this->installmentId),
            $this->channel
        );

        Auth::logout();
    }
}
