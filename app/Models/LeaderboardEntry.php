<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        "chatter_id", "period_type", "period_key", "metric",
        "value", "rank", "country",
    ];

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
