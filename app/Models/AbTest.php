<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbTest extends Model
{
    use HasUuids;

    protected $fillable = [
        "name", "status", "metric", "confidence_level", "traffic_split",
        "winner_variant_id", "started_at", "ended_at", "created_by",
    ];

    protected function casts(): array
    {
        return [
            "traffic_split" => "array",
            "started_at" => "datetime",
            "ended_at" => "datetime",
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(AbTestVariant::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ChatterAbAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "created_by");
    }
}
