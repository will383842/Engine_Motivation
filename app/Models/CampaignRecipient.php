<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        "campaign_id", "chatter_id", "status", "channel",
        "sent_at", "delivered_at", "failed_at", "error_message", "external_msg_id",
    ];

    protected function casts(): array
    {
        return [
            "sent_at" => "datetime",
            "delivered_at" => "datetime",
            "failed_at" => "datetime",
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
