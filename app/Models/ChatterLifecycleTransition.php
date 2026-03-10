<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterLifecycleTransition extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        "chatter_id", "from_state", "to_state",
        "reason", "triggered_by", "metadata",
    ];

    protected function casts(): array
    {
        return [
            "metadata" => "array",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
