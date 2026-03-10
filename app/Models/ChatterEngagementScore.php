<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterEngagementScore extends Model
{
    use HasUuids;

    const CREATED_AT = null;

    protected $fillable = [
        "chatter_id", "engagement_score", "activity_score",
        "revenue_score", "responsiveness_score", "gamification_score",
        "growth_score", "trend", "percentile",
    ];

    protected function casts(): array
    {
        return [
            "engagement_score" => "decimal:2",
            "activity_score" => "decimal:2",
            "revenue_score" => "decimal:2",
            "responsiveness_score" => "decimal:2",
            "gamification_score" => "decimal:2",
            "growth_score" => "decimal:2",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
