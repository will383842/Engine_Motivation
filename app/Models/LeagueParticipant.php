<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueParticipant extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        "league_id", "chatter_id", "weekly_xp",
        "rank", "promoted", "relegated",
    ];

    protected function casts(): array
    {
        return [
            "promoted" => "boolean",
            "relegated" => "boolean",
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
