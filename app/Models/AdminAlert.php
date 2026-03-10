<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAlert extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        "severity", "category", "message", "channels_notified",
        "acknowledged_by", "acknowledged_at",
    ];

    protected function casts(): array
    {
        return [
            "channels_notified" => "array",
            "acknowledged_at" => "datetime",
        ];
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "acknowledged_by");
    }
}
