<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function log(string $module, string $action, ?string $description = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function recent(int $limit = 20): Collection
    {
        return ActivityLog::query()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function userActivity(int $userId): Collection
    {
        return ActivityLog::query()
            ->with('user')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }
}
