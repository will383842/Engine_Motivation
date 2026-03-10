<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterEvent extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        "chatter_id", "event_type", "event_data",
        "firebase_event_id", "occurred_at",
    ];

    protected function casts(): array
    {
        return [
            "event_data" => "array",
            "occurred_at" => "datetime",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
