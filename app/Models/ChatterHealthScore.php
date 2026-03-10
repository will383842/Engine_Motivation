<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterHealthScore extends Model
{
    use HasUuids;

    protected $fillable = [
        "chatter_id", "health_score", "churn_risk",
        "predicted_ltv_cents", "factors", "snapshot_date",
    ];

    protected function casts(): array
    {
        return [
            "health_score" => "decimal:2",
            "churn_risk" => "decimal:2",
            "factors" => "array",
            "snapshot_date" => "date",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
