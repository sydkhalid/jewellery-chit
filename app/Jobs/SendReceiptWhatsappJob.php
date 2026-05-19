<?php

namespace App\Jobs;

use App\Models\ChitReceipt;
use App\Services\ReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class SendReceiptWhatsappJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $receiptId,
        private readonly ?int $actorId = null
    ) {
    }

    public function handle(ReceiptService $receipts): void
    {
        if ($this->actorId) {
            Auth::loginUsingId($this->actorId);
        }

        $receipts->sendWhatsappReceipt(ChitReceipt::query()->findOrFail($this->receiptId));

        Auth::logout();
    }
}
