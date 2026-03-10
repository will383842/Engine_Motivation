<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasUuids;

    protected $fillable = [
        "name", "status", "channel", "template_id", "segment_id",
        "scheduled_at", "timezone_aware", "send_rate_per_second",
        "total_recipients", "sent_count", "delivered_count", "failed_count",
        "ab_test_id", "created_by",
    ];

    protected function casts(): array
    {
        return [
            "scheduled_at" => "datetime",
            "timezone_aware" => "boolean",
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, "template_id");
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "created_by");
    }
}
