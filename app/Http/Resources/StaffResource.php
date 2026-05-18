<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'branch_id' => $this->branch_id,
            'status' => $this->status,
            'role' => $this->getRoleNames()->first(),
            'permissions' => $this->when($request->boolean('include_permissions'), fn () => $this->getAllPermissions()->pluck('name')->values()->all()),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'branch_code' => $this->branch->branch_code,
                'name' => $this->branch->name,
            ] : null),
            'collections_count' => $this->whenCounted('staffCollections'),
            'assigned_enrollments_count' => $this->whenCounted('assignedChitEnrollments'),
            'cash_handovers_count' => $this->whenCounted('staffCashHandovers'),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
