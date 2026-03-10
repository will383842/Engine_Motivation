<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WhatsAppHealthLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = "whatsapp_health_logs";

    protected $fillable = [
        "quality_rating", "tier",
        "sent_24h", "delivered_24h", "blocked_24h",
        "block_rate", "circuit_breaker_state", "warmup_week",
        "daily_limit", "budget_spent_cents", "checked_at",
    ];

    protected function casts(): array
    {
        return [
            "block_rate" => "decimal:4",
            "checked_at" => "datetime",
        ];
    }
}
