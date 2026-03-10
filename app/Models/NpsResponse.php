<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NpsResponse extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        "chatter_id", "score", "comment", "channel", "survey_version",
    ];

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
