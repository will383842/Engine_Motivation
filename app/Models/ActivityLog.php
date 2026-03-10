<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id", "admin_id", "action", "entity_type", "entity_id",
        "old_values", "new_values", "ip_address", "user_agent",
    ];

    protected function casts(): array
    {
        return [
            "old_values" => "array",
            "new_values" => "array",
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "admin_id");
    }
}
