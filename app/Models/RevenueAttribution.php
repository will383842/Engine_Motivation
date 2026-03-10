<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueAttribution extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        "chatter_id", "commission_cents", "attributed_to",
        "attributed_message_id", "attribution_window_hours", "firebase_event_id",
    ];

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
