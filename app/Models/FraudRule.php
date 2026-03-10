<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudRule extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        "name", "description", "rule_type", "conditions",
        "action", "is_active", "created_by",
    ];

    protected function casts(): array
    {
        return [
            "conditions" => "array",
            "is_active" => "boolean",
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "created_by");
    }
}
