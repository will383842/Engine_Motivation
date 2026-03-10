<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    use HasUuids;

    protected $fillable = [
        "tier", "week_key", "max_participants",
        "promotion_count", "relegation_count",
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(LeagueParticipant::class);
    }
}
