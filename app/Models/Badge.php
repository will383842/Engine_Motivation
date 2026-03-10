<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'names',
        'descriptions',
        'icon_url',
        'category',
        'xp_reward',
        'criteria',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'names' => 'json',
            'descriptions' => 'json',
            'criteria' => 'json',
            'is_active' => 'boolean',
            'xp_reward' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function chatterBadges(): HasMany
    {
        return $this->hasMany(ChatterBadge::class);
    }
}
