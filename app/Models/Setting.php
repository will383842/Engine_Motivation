<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'description',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }
}
