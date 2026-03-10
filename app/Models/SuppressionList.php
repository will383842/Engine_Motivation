<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuppressionList extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $table = "suppression_list";

    protected $fillable = [
        "chatter_id", "channel", "reason", "source", "notes",
        "suppressed_at", "expires_at", "lifted_at", "lifted_by",
    ];

    protected function casts(): array
    {
        return [
            "suppressed_at" => "datetime",
            "expires_at" => "datetime",
            "lifted_at" => "datetime",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }

    public function liftedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "lifted_by");
    }
}
