<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterBadge extends Model
{
    use HasUuids;

    protected $fillable = [
        'chatter_id',
        'badge_id',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }
}
