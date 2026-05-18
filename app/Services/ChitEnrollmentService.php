<?php

namespace App\Services;

use App\Http\Resources\ChitEnrollmentResource;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ChitEnrollment;
use App\Models\ChitScheme;
use App\Repositories\ChitEnrollmentRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChitEnrollmentService
{
    public function __construct(
        private readonly ChitEnrollmentRepository $enrollments,
        private readonly InstallmentService $installmentService
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEnrollment(array $data): ChitEnrollment
    {
        return DB::transaction(function () use ($data): ChitEnrollment {
            $scheme = $this->activeScheme((int) $data['scheme_id']);
            $this->validateMonthlyAmount($scheme, $data);

            $agreementFile = $data['agreement_file'] ?? null;
            $enrollmentData = $this->buildEnrollmentData($scheme, $data);
            $enrollmentData['chit_no'] = $this->generateChitNumber();
            $enrollmentData['created_by'] = Auth::id();
            $enrollmentData['updated_by'] = Auth::id();

            if ($agreementFile instanceof UploadedFile) {
                $enrollmentData['agreement_file'] = $this->uploadAgreementFile($agreementFile);
            }

            $enrollment = $this->enrollments->create($enrollmentData);
            $this->installmentService->generateSchedule($enrollment);
            $this->createEnrollmentReceipt($enrollment);

            $enrollment->load(['customer', 'scheme', 'branch', 'assignedStaff', 'installments']);
            $this->logEnrollmentAction($enrollment, 'create', 'created', null, $enrollment->toArray());

            if ($agreementFile instanceof UploadedFile) {
                $this->logEnrollmentAction($enrollment, 'agreement_upload', 'agreement uploaded', null, [
                    'agreement_file' => $enrollment->agreement_file,
                ]);
            }

            return $enrollment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEnrollment(ChitEnrollment $enrollment, array $data): ChitEnrollment
    {
        return DB::transaction(function () use ($enrollment, $data): ChitEnrollment {
            $scheme = $this->activeScheme((int) $data['scheme_id']);
            $this->validateMonthlyAmount($scheme, $data);

            $oldValues = $enrollment->load(['installments'])->toArray();
            $agreementFile = $data['agreement_file'] ?? null;
            $enrollmentData = $this->buildEnrollmentData($scheme, $data);
            $enrollmentData['total_paid'] = $enrollment->total_paid;
            $enrollmentData['total_pending'] = max(0, (float) $enrollmentData['total_payable'] - (float) $enrollment->total_paid);
            $enrollmentData['status'] = $enrollment->status;
            $enrollmentData['updated_by'] = Auth::id();

            if ($agreementFile instanceof UploadedFile) {
                $enrollmentData['agreement_file'] = $this->uploadAgreementFile($agreementFile);
            }

            $enrollment = $this->enrollments->update($enrollment, $enrollmentData);

            if (! $enrollment->payments()->exists()) {
                $this->installmentService->regenerateSchedule($enrollment);
            }

            $enrollment->load(['customer', 'scheme', 'branch', 'assignedStaff', 'installments']);
            $this->logEnrollmentAction($enrollment, 'update', 'updated', $oldValues, $enrollment->toArray());

            if ($agreementFile instanceof UploadedFile) {
                $this->logEnrollmentAction($enrollment, 'agreement_upload', 'agreement uploaded', null, [
                    'agreement_file' => $enrollment->agreement_file,
                ]);
            }

            return $enrollment;
        });
    }

    public function deleteEnrollment(ChitEnrollment $enrollment): bool
    {
        return DB::transaction(function () use ($enrollment): bool {
            if ($enrollment->payments()->exists()) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Enrollment has payments and cannot be deleted. Cancel the enrollment instead.',
                ]);
            }

            $oldValues = $enrollment->toArray();
            $deleted = $this->enrollments->delete($enrollment);
            $this->logEnrollmentAction($enrollment, 'delete', 'deleted', $oldValues, null);

            return $deleted;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancelEnrollment(ChitEnrollment $enrollment, array $data): ChitEnrollment
    {
        return DB::transaction(function () use ($enrollment, $data): ChitEnrollment {
            if (in_array($enrollment->status, ['cancelled', 'closed'], true)) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Enrollment is already '.$enrollment->status.'.',
                ]);
            }

            $oldValues = $enrollment->toArray();

            $this->enrollments->cancel($enrollment, [
                'customer_id' => $enrollment->customer_id,
                'cancellation_date' => $data['cancellation_date'],
                'reason' => $data['reason'],
                'refund_amount' => $data['refund_amount'] ?? 0,
                'deduction_amount' => $data['deduction_amount'] ?? 0,
                'cancelled_by' => Auth::id(),
            ]);

            $enrollment = $this->enrollments->update($enrollment, [
                'status' => 'cancelled',
                'updated_by' => Auth::id(),
            ]);

            $enrollment->load(['customer', 'scheme', 'branch', 'assignedStaff', 'cancellations']);
            $this->logEnrollmentAction($enrollment, 'cancellation', 'cancelled', $oldValues, $enrollment->toArray());

            return $enrollment;
        });
    }

    public function generateChitNumber(): string
    {
        $nextId = (int) ChitEnrollment::withTrashed()->max('id') + 1;

        do {
            $number = 'CHIT'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (ChitEnrollment::withTrashed()->where('chit_no', $number)->exists());

        return $number;
    }

    public function calculateMaturityDate(mixed $startDate, int $durationMonths): Carbon
    {
        return Carbon::parse($startDate)->addMonthsNoOverflow($durationMonths);
    }

    public function calculateMonthlyDueDate(mixed $startDate): int
    {
        return (int) Carbon::parse($startDate)->format('d');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function calculateTotalPayable(ChitScheme $scheme, array $data): float
    {
        $monthlyAmount = $this->resolveMonthlyAmount($scheme, $data);

        return round($monthlyAmount * (int) $scheme->duration_months, 2);
    }

    public function uploadAgreement(ChitEnrollment $enrollment, mixed $file): ChitEnrollment
    {
        if (! $file instanceof UploadedFile) {
            return $enrollment;
        }

        return DB::transaction(function () use ($enrollment, $file): ChitEnrollment {
            $oldValues = $enrollment->toArray();
            $enrollment = $this->enrollments->update($enrollment, [
                'agreement_file' => $this->uploadAgreementFile($file),
                'updated_by' => Auth::id(),
            ]);

            $this->logEnrollmentAction($enrollment, 'agreement_upload', 'agreement uploaded', $oldValues, $enrollment->toArray());

            return $enrollment;
        });
    }

    /**
     * Enrollment receipts are generated by the payment module. This method keeps
     * the enrollment workflow explicit without creating a payment-backed receipt.
     *
     * @return array<string, mixed>
     */
    public function createEnrollmentReceipt(ChitEnrollment $enrollment): array
    {
        return [
            'chit_no' => $enrollment->chit_no,
            'customer_id' => $enrollment->customer_id,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareApiEnrollmentData(ChitEnrollment $enrollment): array
    {
        return [
            'enrollment' => new ChitEnrollmentResource(
                $enrollment->loadMissing(['customer', 'scheme', 'branch', 'assignedStaff', 'installments'])
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildEnrollmentData(ChitScheme $scheme, array $data): array
    {
        $monthlyAmount = $this->resolveMonthlyAmount($scheme, $data);
        $totalPayable = $this->calculateTotalPayable($scheme, $data);
        $startDate = Carbon::parse($data['start_date']);

        return [
            'customer_id' => $data['customer_id'],
            'scheme_id' => $scheme->id,
            'branch_id' => $data['branch_id'] ?? Auth::user()?->branch_id,
            'assigned_staff_id' => $data['assigned_staff_id'] ?? null,
            'start_date' => $startDate->toDateString(),
            'monthly_due_date' => $this->calculateMonthlyDueDate($startDate),
            'maturity_date' => $this->calculateMaturityDate($startDate, (int) $scheme->duration_months)->toDateString(),
            'remarks' => $data['remarks'] ?? null,
            'total_months' => $scheme->duration_months,
            'monthly_amount' => $scheme->scheme_type === 'gold_weight' ? null : $monthlyAmount,
            'total_payable' => $totalPayable,
            'total_paid' => 0,
            'total_pending' => $totalPayable,
            'status' => 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateMonthlyAmount(ChitScheme $scheme, array $data): void
    {
        if ($scheme->scheme_type !== 'flexible_amount') {
            return;
        }

        $monthlyAmount = (float) ($data['monthly_amount'] ?? 0);

        if ($monthlyAmount < (float) $scheme->min_amount || $monthlyAmount > (float) $scheme->max_amount) {
            throw ValidationException::withMessages([
                'monthly_amount' => 'Monthly amount must be between scheme minimum and maximum amount.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveMonthlyAmount(ChitScheme $scheme, array $data): float
    {
        return match ($scheme->scheme_type) {
            'fixed_amount' => (float) $scheme->monthly_amount,
            'flexible_amount' => (float) ($data['monthly_amount'] ?? 0),
            default => 0.0,
        };
    }

    private function activeScheme(int $schemeId): ChitScheme
    {
        $scheme = ChitScheme::findOrFail($schemeId);

        if ($scheme->status !== 'active') {
            throw ValidationException::withMessages([
                'scheme_id' => 'Selected scheme must be active.',
            ]);
        }

        return $scheme;
    }

    private function uploadAgreementFile(UploadedFile $file): string
    {
        return $file->store('chit-enrollments/agreements', 'public');
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logEnrollmentAction(
        ChitEnrollment $enrollment,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'chit_enrollments',
            'description' => "Enrollment {$enrollment->chit_no} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => ChitEnrollment::class,
            'auditable_id' => $enrollment->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
