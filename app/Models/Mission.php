<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'names',
        'descriptions',
        'type',
        'status',
        'criteria',
        'target_count',
        'xp_reward',
        'bonus_cents',
        'badge_id',
        'available_from',
        'available_until',
        'cooldown_hours',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'names' => 'json',
            'descriptions' => 'json',
            'criteria' => 'json',
            'target_count' => 'integer',
            'xp_reward' => 'integer',
            'bonus_cents' => 'integer',
            'cooldown_hours' => 'integer',
            'sort_order' => 'integer',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function chatterMissions(): HasMany
    {
        return $this->hasMany(ChatterMission::class);
    }
}
