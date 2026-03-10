<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id", "source", "external_event_id", "event_type",
        "payload", "status", "processed_at", "attempts",
    ];

    protected function casts(): array
    {
        return [
            "payload" => "array",
            "processed_at" => "datetime",
        ];
    }
}
