<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbTestVariant extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        "ab_test_id", "name", "template_id", "weight",
        "sent_count", "delivered_count", "read_count", "conversion_count",
    ];

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(AbTest::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, "template_id");
    }
}
