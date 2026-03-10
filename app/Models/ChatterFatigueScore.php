<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterFatigueScore extends Model
{
    use HasUuids;

    const CREATED_AT = null;

    protected $fillable = [
        "chatter_id", "channel", "fatigue_score",
        "messages_sent_7d", "messages_opened_7d", "messages_clicked_7d",
        "last_interaction_at", "consecutive_ignored", "frequency_multiplier",
    ];

    protected function casts(): array
    {
        return [
            "fatigue_score" => "decimal:2",
            "frequency_multiplier" => "decimal:2",
            "last_interaction_at" => "datetime",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
