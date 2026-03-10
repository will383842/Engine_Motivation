<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterAbAssignment extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        "chatter_id", "ab_test_id", "variant_id", "assigned_at", "converted",
    ];

    protected function casts(): array
    {
        return [
            "assigned_at" => "datetime",
            "converted" => "boolean",
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(AbTest::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(AbTestVariant::class, "variant_id");
    }
}
