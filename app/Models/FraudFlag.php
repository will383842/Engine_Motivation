<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudFlag extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        "chatter_id", "flag_type", "severity", "evidence",
        "resolved", "resolved_by", "resolved_at",
    ];

    protected function casts(): array
    {
        return [
            "evidence" => "array",
            "resolved" => "boolean",
            "resolved_at" => "datetime",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "resolved_by");
    }
}
