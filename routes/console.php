<?php

use App\Jobs\SendDueReminderJob;
use App\Models\ChitInstallment;
use App\Models\ShopSetting;
use App\Services\InstallmentService;
use App\Services\PendingDueService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('installments:mark-overdue', function (InstallmentService $installments): int {
    $count = $installments->markOverdueInstallments();

    $this->info("Marked {$count} overdue installments.");

    return 0;
})->purpose('Mark pending installments as overdue when their due date has passed');

Artisan::command('reminders:send-due {--channel=whatsapp} {--queue}', function (PendingDueService $pendingDues): int {
    $channel = (string) $this->option('channel');

    if (! in_array($channel, ['whatsapp', 'sms'], true)) {
        $this->error('Reminder channel must be whatsapp or sms.');

        return 1;
    }

    $installments = ChitInstallment::query()
        ->whereIn('status', ['pending', 'partial', 'overdue'])
        ->where('balance_amount', '>', 0)
        ->whereDate('due_date', '<=', today())
        ->where(function ($query): void {
            $query->whereNull('last_reminder_at')
                ->orWhereDate('last_reminder_at', '<', today());
        })
        ->orderBy('due_date')
        ->limit(500)
        ->get();

    if ($this->option('queue')) {
        $installments->each(fn (ChitInstallment $installment): mixed => SendDueReminderJob::dispatch($installment->id, $channel, null)->onQueue('reminders'));
        $this->info("Queued {$installments->count()} due reminders.");

        return 0;
    }

    $sent = 0;

    foreach ($installments as $installment) {
        $pendingDues->sendDueReminder($installment, $channel);
        $sent++;
    }

    $this->info("Sent {$sent} due reminders.");

    return 0;
})->purpose('Send or queue due reminders for unpaid installments');

Schedule::command('installments:mark-overdue')
    ->dailyAt('06:00')
    ->withoutOverlapping();

Schedule::command('reminders:send-due --channel=whatsapp --queue')
    ->dailyAt('09:00')
    ->withoutOverlapping();

Schedule::command('backup:run --only-db --disable-notifications')
    ->dailyAt('23:30')
    ->when(fn (): bool => (bool) ShopSetting::getByKey('backup_enabled', true))
    ->withoutOverlapping();
