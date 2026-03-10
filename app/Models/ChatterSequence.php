<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatterSequence extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'chatter_id',
        'sequence_id',
        'current_step_id',
        'current_step_order',
        'status',
        'enrolled_at',
        'next_step_at',
        'completed_at',
        'exit_reason',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'next_step_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(SequenceStep::class, 'current_step_id');
    }
}
