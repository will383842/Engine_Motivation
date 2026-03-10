<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Streak extends Model
{
    use HasUuids;

    protected $fillable = [
        'chatter_id',
        'current_count',
        'longest_count',
        'previous_count',
        'last_activity_date',
        'streak_frozen_until',
        'freeze_count_used',
        'freeze_count_max',
        'started_at',
        'broken_at',
    ];

    protected function casts(): array
    {
        return [
            'current_count' => 'integer',
            'longest_count' => 'integer',
            'previous_count' => 'integer',
            'freeze_count_used' => 'integer',
            'freeze_count_max' => 'integer',
            'last_activity_date' => 'date',
            'streak_frozen_until' => 'datetime',
            'started_at' => 'datetime',
            'broken_at' => 'datetime',
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
