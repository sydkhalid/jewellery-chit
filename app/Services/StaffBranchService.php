<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ChitPayment;
use App\Models\StaffCashHandover;
use App\Models\User;
use App\Repositories\BranchRepository;
use App\Repositories\StaffCashHandoverRepository;
use App\Repositories\StaffRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StaffBranchService
{
    public function __construct(
        private readonly BranchRepository $branches,
        private readonly StaffRepository $staff,
        private readonly StaffCashHandoverRepository $handovers
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createBranch(array $data): Branch
    {
        return DB::transaction(function () use ($data): Branch {
            $payload = $this->normalizeBranchData($data);
            $branch = $this->branches->create($payload + [
                'branch_code' => filled($payload['branch_code'] ?? null) ? $payload['branch_code'] : $this->generateBranchCode(),
                'created_by' => Auth::id(),
            ]);

            $this->logAction($branch, 'create', 'created', 'branches', null, $branch->toArray());

            return $branch;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBranch(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data): Branch {
            $oldValues = $branch->toArray();
            $branch = $this->branches->update($branch, $this->normalizeBranchData($data) + [
                'updated_by' => Auth::id(),
            ]);

            $this->logAction($branch, 'update', 'updated', 'branches', $oldValues, $branch->toArray());

            return $branch;
        });
    }

    public function deleteBranch(Branch $branch): Branch
    {
        return DB::transaction(function () use ($branch): Branch {
            $branch->loadCount(['users', 'enrollments', 'payments']);
            $oldValues = $branch->toArray();

            if ($branch->users_count > 0 || $branch->enrollments_count > 0 || $branch->payments_count > 0) {
                $branch = $this->branches->update($branch, [
                    'status' => 'inactive',
                    'updated_by' => Auth::id(),
                ]);
                $this->logAction($branch, 'delete', 'marked inactive', 'branches', $oldValues, $branch->toArray());

                return $branch;
            }

            $branch->update(['deleted_by' => Auth::id()]);
            $this->branches->delete($branch);
            $branch->status = 'deleted';

            $this->logAction($branch, 'delete', 'deleted', 'branches', $oldValues, $branch->toArray());

            return $branch;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createStaff(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $staff = $this->staff->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password' => Hash::make((string) $data['password']),
                'branch_id' => $data['branch_id'],
                'status' => $data['status'] ?? 'active',
            ]);

            $this->assignRole($staff, (string) $data['role'], false);
            $staff->load('branch');
            $this->logAction($staff, 'create', 'created', 'staff', null, $staff->toArray());

            return $staff;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStaff(User $staff, array $data): User
    {
        return DB::transaction(function () use ($staff, $data): User {
            $oldValues = $staff->load('roles')->toArray();
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'branch_id' => $data['branch_id'],
                'status' => $data['status'] ?? 'active',
            ];

            if (filled($data['password'] ?? null)) {
                $payload['password'] = Hash::make((string) $data['password']);
            }

            $staff = $this->staff->update($staff, $payload);
            $this->assignRole($staff, (string) $data['role'], false);
            $staff->load(['branch', 'roles']);
            $this->logAction($staff, 'update', 'updated', 'staff', $oldValues, $staff->toArray());

            return $staff;
        });
    }

    public function deleteStaff(User $staff): User
    {
        return DB::transaction(function () use ($staff): User {
            $staff->loadCount(['staffCollections', 'assignedChitEnrollments', 'staffCashHandovers']);
            $oldValues = $staff->toArray();

            if ($staff->staff_collections_count > 0 || $staff->assigned_chit_enrollments_count > 0 || $staff->staff_cash_handovers_count > 0) {
                $staff = $this->changeStaffStatus($staff, 'inactive', false);
                $this->logAction($staff, 'delete', 'marked inactive', 'staff', $oldValues, $staff->toArray());

                return $staff;
            }

            $staff->syncRoles([]);
            $this->staff->delete($staff);
            $staff->status = 'deleted';
            $this->logAction($staff, 'delete', 'deleted', 'staff', $oldValues, $staff->toArray());

            return $staff;
        });
    }

    public function changeStaffStatus(User $staff, string $status, bool $log = true): User
    {
        if (! in_array($status, ['active', 'inactive'], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid staff status.']);
        }

        $oldValues = $staff->toArray();
        $staff = $this->staff->update($staff, ['status' => $status]);

        if ($log) {
            $this->logAction($staff, 'update', "marked {$status}", 'staff', $oldValues, $staff->toArray());
        }

        return $staff;
    }

    public function assignRole(User $staff, string $role, bool $log = true): User
    {
        if (! in_array($role, ['Admin', 'Manager', 'Staff'], true)) {
            throw ValidationException::withMessages(['role' => 'Invalid staff role.']);
        }

        $oldValues = $staff->loadMissing('roles')->toArray();
        $staff->syncRoles([$role]);

        if ($log) {
            $staff->load('roles');
            $this->logAction($staff, 'update', 'role updated', 'staff', $oldValues, $staff->toArray());
        }

        return $staff->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCashHandover(array $data): StaffCashHandover
    {
        return DB::transaction(function () use ($data): StaffCashHandover {
            $staff = User::findOrFail((int) $data['staff_id']);
            $total = $this->calculateHandoverTotal($data);

            $handover = $this->handovers->create([
                'handover_no' => $this->generateHandoverNumber(),
                'staff_id' => $staff->id,
                'branch_id' => $data['branch_id'] ?? $staff->branch_id,
                'handover_date' => $data['handover_date'],
                'cash_amount' => round((float) ($data['cash_amount'] ?? 0), 2),
                'upi_amount' => round((float) ($data['upi_amount'] ?? 0), 2),
                'card_amount' => round((float) ($data['card_amount'] ?? 0), 2),
                'bank_amount' => round((float) ($data['bank_amount'] ?? 0), 2),
                'total_amount' => $total,
                'status' => 'pending',
                'remarks' => $data['remarks'] ?? null,
            ]);

            $handover->load(['staff', 'branch', 'receiver']);
            $this->logAction($handover, 'create', 'created', 'staff_cash_handovers', null, $handover->toArray());

            return $handover;
        });
    }

    public function receiveCashHandover(StaffCashHandover $handover): StaffCashHandover
    {
        return DB::transaction(function () use ($handover): StaffCashHandover {
            $this->assertCanReceiveHandover();
            $this->assertPendingHandover($handover);

            $oldValues = $handover->toArray();
            $handover = $this->handovers->update($handover, [
                'status' => 'received',
                'received_by' => Auth::id(),
            ]);

            $handover->load(['staff', 'branch', 'receiver']);
            $this->logAction($handover, 'update', 'received', 'staff_cash_handovers', $oldValues, $handover->toArray());

            return $handover;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function rejectCashHandover(StaffCashHandover $handover, array $data): StaffCashHandover
    {
        return DB::transaction(function () use ($handover, $data): StaffCashHandover {
            $this->assertCanReceiveHandover();
            $this->assertPendingHandover($handover);

            $oldValues = $handover->toArray();
            $handover = $this->handovers->update($handover, [
                'status' => 'rejected',
                'received_by' => Auth::id(),
                'remarks' => $data['remarks'],
            ]);

            $handover->load(['staff', 'branch', 'receiver']);
            $this->logAction($handover, 'update', 'rejected', 'staff_cash_handovers', $oldValues, $handover->toArray());

            return $handover;
        });
    }

    public function generateHandoverNumber(): string
    {
        $prefix = 'HND';
        $nextId = (int) StaffCashHandover::withTrashed()->max('id') + 1;

        do {
            $number = $prefix.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (StaffCashHandover::withTrashed()->where('handover_no', $number)->exists());

        return $number;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStaffCollectionSummary(User $staff): array
    {
        $payments = ChitPayment::query()
            ->where('staff_id', $staff->id)
            ->where('status', 'success');

        return [
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'total_collection' => round((float) (clone $payments)->sum('total_amount'), 2),
            'today_collection' => round((float) (clone $payments)->whereDate('payment_date', today())->sum('total_amount'), 2),
            'month_collection' => round((float) (clone $payments)->whereMonth('payment_date', today()->month)->whereYear('payment_date', today()->year)->sum('total_amount'), 2),
            'payment_count' => (clone $payments)->count(),
            'pending_handovers' => StaffCashHandover::where('staff_id', $staff->id)->where('status', 'pending')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getBranchCollectionSummary(Branch $branch): array
    {
        $payments = ChitPayment::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'success');

        return [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'total_collection' => round((float) (clone $payments)->sum('total_amount'), 2),
            'today_collection' => round((float) (clone $payments)->whereDate('payment_date', today())->sum('total_amount'), 2),
            'month_collection' => round((float) (clone $payments)->whereMonth('payment_date', today()->month)->whereYear('payment_date', today()->year)->sum('total_amount'), 2),
            'payment_count' => (clone $payments)->count(),
            'staff_count' => $branch->users()->count(),
        ];
    }

    public function generateBranchCode(): string
    {
        $prefix = 'BR';
        $nextId = (int) Branch::withTrashed()->max('id') + 1;

        do {
            $number = $prefix.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
            $nextId++;
        } while (Branch::withTrashed()->where('branch_code', $number)->exists());

        return $number;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeBranchData(array $data): array
    {
        return [
            'branch_code' => $data['branch_code'] ?? null,
            'name' => $data['name'],
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'pincode' => $data['pincode'] ?? '',
            'status' => $data['status'] ?? 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function calculateHandoverTotal(array $data): float
    {
        return round(
            (float) ($data['cash_amount'] ?? 0)
            + (float) ($data['upi_amount'] ?? 0)
            + (float) ($data['card_amount'] ?? 0)
            + (float) ($data['bank_amount'] ?? 0),
            2
        );
    }

    private function assertCanReceiveHandover(): void
    {
        if (! (Auth::user()?->hasAnyRole(['Admin', 'Manager']) ?? false)) {
            throw ValidationException::withMessages([
                'handover' => 'Only Admin or Manager can receive or reject handovers.',
            ]);
        }
    }

    private function assertPendingHandover(StaffCashHandover $handover): void
    {
        if ($handover->status !== 'pending') {
            throw ValidationException::withMessages([
                'handover' => 'Only pending handovers can be processed.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logAction(
        Model $model,
        string $event,
        string $action,
        string $module,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => $module,
            'description' => ucfirst(str_replace('_', ' ', $module))." {$action}.",
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
