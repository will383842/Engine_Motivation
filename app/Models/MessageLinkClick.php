<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLinkClick extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        "message_log_id", "chatter_id", "url",
        "clicked_at", "ip_address", "user_agent",
    ];

    protected function casts(): array
    {
        return [
            "clicked_at" => "datetime",
        ];
    }

    public function messageLog(): BelongsTo
    {
        return $this->belongsTo(MessageLog::class);
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
