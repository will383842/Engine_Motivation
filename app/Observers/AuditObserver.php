<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Global observer that auto-logs create/update/delete on admin-managed models.
 * Register in AppServiceProvider for: Chatter, Campaign, Sequence, Mission, Badge, etc.
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log('created', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();
        if (empty($dirty)) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $dirty);
        $this->log('updated', $model, $original, $dirty);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model, $model->getOriginal(), null);
    }

    private function log(string $action, Model $model, ?array $oldValues, ?array $newValues): void
    {
        // Skip if no admin context (e.g. webhook processing, CLI)
        $admin = auth()->user();
        if (!$admin) {
            return;
        }

        // Skip logging ActivityLog itself (prevent recursion)
        if ($model instanceof ActivityLog) {
            return;
        }

        // Redact sensitive fields
        $redact = ['password', 'email', 'phone', 'whatsapp_phone', 'bot_token_encrypted'];
        $oldValues = $this->redact($oldValues, $redact);
        $newValues = $this->redact($newValues, $redact);

        ActivityLog::create([
            'id' => Str::uuid()->toString(),
            'admin_id' => $admin->id,
            'action' => $action,
            'entity_type' => $model->getMorphClass(),
            'entity_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private function redact(?array $values, array $fields): ?array
    {
        if (!$values) {
            return $values;
        }

        foreach ($fields as $field) {
            if (isset($values[$field])) {
                $values[$field] = '***REDACTED***';
            }
        }

        return $values;
    }
}
