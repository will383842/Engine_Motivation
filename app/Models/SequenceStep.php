<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SequenceStep extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'sequence_id',
        'step_order',
        'type',
        'template_id',
        'channel',
        'delay_seconds',
        'condition_rules',
        'ab_test_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'condition_rules' => 'array',
            'metadata' => 'array',
        ];
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }
}
