<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sequence extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'status',
        'trigger_event',
        'segment_id',
        'is_repeatable',
        'priority',
        'max_concurrent',
        'exit_conditions',
        'version',
        'parent_sequence_id',
        'snapshot_before_edit',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_repeatable' => 'boolean',
            'exit_conditions' => 'array',
            'snapshot_before_edit' => 'array',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SequenceStep::class);
    }

    public function chatterSequences(): HasMany
    {
        return $this->hasMany(ChatterSequence::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function parentSequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'parent_sequence_id');
    }
}
