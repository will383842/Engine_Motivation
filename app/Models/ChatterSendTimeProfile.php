<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterSendTimeProfile extends Model
{
    use HasUuids;

    const CREATED_AT = null;

    protected $fillable = [
        "chatter_id", "best_hour_local", "best_day_of_week",
        "interaction_heatmap", "sample_size", "confidence",
    ];

    protected function casts(): array
    {
        return [
            "interaction_heatmap" => "array",
            "confidence" => "decimal:2",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
