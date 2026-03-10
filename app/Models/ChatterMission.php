<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterMission extends Model
{
    use HasUuids;

    protected $fillable = [
        'chatter_id',
        'mission_id',
        'status',
        'progress_count',
        'target_count',
        'assigned_at',
        'completed_at',
        'expires_at',
        'reward_granted',
    ];

    protected function casts(): array
    {
        return [
            'progress_count' => 'integer',
            'target_count' => 'integer',
            'reward_granted' => 'boolean',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
