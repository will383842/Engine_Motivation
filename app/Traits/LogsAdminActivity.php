<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Str;

/**
 * Auto-log create/update/delete actions to activity_logs.
 * Apply this trait to any Filament Resource or Service that modifies entities.
 */
trait LogsAdminActivity
{
    public static function logActivity(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        $admin = auth()->user();

        ActivityLog::create([
            'id' => Str::uuid()->toString(),
            'admin_id' => $admin?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
