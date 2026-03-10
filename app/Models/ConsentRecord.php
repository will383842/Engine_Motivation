<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentRecord extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        "chatter_id", "consent_type", "granted",
        "ip_address", "user_agent", "consent_text", "version",
        "granted_at", "revoked_at",
    ];

    protected function casts(): array
    {
        return [
            "granted" => "boolean",
            "granted_at" => "datetime",
            "revoked_at" => "datetime",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
