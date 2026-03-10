<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentRule extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'segment_id', 'field', 'operator', 'value', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['value' => 'json', 'sort_order' => 'integer'];
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
