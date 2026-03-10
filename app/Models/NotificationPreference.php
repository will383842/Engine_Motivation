<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'chatter_id',
        'channel',
        'is_opted_in',
        'opted_out_at',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected function casts(): array
    {
        return [
            'is_opted_in' => 'boolean',
            'opted_out_at' => 'datetime',
        ];
    }

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }
}
