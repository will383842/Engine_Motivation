<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramBot extends Model
{
    use HasUuids;

    protected $fillable = [
        "bot_username", "bot_token_encrypted", "role",
        "is_active", "is_restricted", "assigned_chatters_count",
        "total_sent", "total_failed", "health_score",
        "last_health_check_at", "consecutive_failures",
        "notes", "created_by",
    ];

    protected $hidden = [
        "bot_token_encrypted",
    ];

    protected function casts(): array
    {
        return [
            "bot_token_encrypted" => "encrypted",
            "is_active" => "boolean",
            "is_restricted" => "boolean",
            "health_score" => "decimal:2",
            "last_health_check_at" => "datetime",
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "created_by");
    }
}
