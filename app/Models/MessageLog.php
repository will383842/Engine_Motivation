<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageLog extends Model
{
    public $incrementing = false;
    protected $keyType = "string";
    const UPDATED_AT = null;

    protected $fillable = [
        "id", "chatter_id", "channel", "direction", "status",
        "source_type", "source_id", "template_id", "body", "external_msg_id",
        "sent_at", "delivered_at", "read_at", "failed_at",
        "error_code", "cost_cents", "metadata",
        "sender_id", "sender_type",
        "clicked_at", "replied_at", "click_count", "reply_content", "interaction_type",
    ];

    protected function casts(): array
    {
        return [
            "metadata" => "array",
            "sent_at" => "datetime",
            "delivered_at" => "datetime",
            "read_at" => "datetime",
            "failed_at" => "datetime",
            "clicked_at" => "datetime",
            "replied_at" => "datetime",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }

    public function linkClicks(): HasMany
    {
        return $this->hasMany(MessageLinkClick::class);
    }
}
