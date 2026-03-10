<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segment extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'operator',
        'is_dynamic',
        'cached_count',
        'cached_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_dynamic' => 'boolean',
            'cached_count' => 'integer',
            'cached_at' => 'datetime',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(SegmentRule::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}
