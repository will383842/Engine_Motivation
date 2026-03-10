<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplateVariant extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'channel',
        'language',
        'body',
        'media_url',
        'buttons',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'buttons' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }
}
