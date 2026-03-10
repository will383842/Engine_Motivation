<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SenderHealthLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        "sender_type", "sender_id",
        "sent_24h", "delivered_24h", "blocked_24h", "failed_24h",
        "block_rate", "quality_rating", "circuit_breaker_state",
        "health_score", "checked_at",
    ];

    protected function casts(): array
    {
        return [
            "block_rate" => "decimal:4",
            "health_score" => "decimal:2",
            "checked_at" => "datetime",
        ];
    }
}
