<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'category',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MessageTemplateVariant::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}
