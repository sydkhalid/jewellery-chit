<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function log(
        string $module,
        string $event,
        Model|string|null $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null
    ): AuditLog {
        $auditableType = $auditable instanceof Model ? $auditable::class : ($auditable ?: $module);
        $auditableId = $auditable instanceof Model ? (int) $auditable->getKey() : 0;

        return AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'event' => $event,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: ($description ? ['description' => $description] : null),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function logCreate(string $module, Model $model): AuditLog
    {
        return $this->log($module, 'create', $model, [], $model->toArray(), "{$module} created.");
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function logUpdate(string $module, Model $model, array $oldValues, array $newValues): AuditLog
    {
        return $this->log($module, 'update', $model, $oldValues, $newValues, "{$module} updated.");
    }

    public function logDelete(string $module, Model $model): AuditLog
    {
        return $this->log($module, 'delete', $model, $model->toArray(), [], "{$module} deleted.");
    }

    public function logAction(string $module, string $event, ?string $description = null): AuditLog
    {
        return $this->log($module, $event, $module, [], [], $description);
    }
}
