<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        "name", "report_type", "schedule_cron", "recipients",
        "filters", "format", "is_active", "last_sent_at", "created_by",
    ];

    protected function casts(): array
    {
        return [
            "recipients" => "array",
            "filters" => "array",
            "is_active" => "boolean",
            "last_sent_at" => "datetime",
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "created_by");
    }
}
