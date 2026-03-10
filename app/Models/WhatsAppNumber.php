<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppNumber extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_numbers';

    protected $fillable = [
        'phone_number',
        'twilio_sid',
        'display_name',
        'country_code',
        'is_active',
        'warmup_start_date',
        'warmup_week',
        'current_daily_limit',
        'current_tier',
        'quality_rating',
        'circuit_breaker_state',
        'circuit_breaker_until',
        'circuit_breaker_reason',
        'health_score',
        'total_sent',
        'total_delivered',
        'total_blocked',
        'total_cost_cents',
        'daily_budget_cap_cents',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'warmup_start_date' => 'date',
            'circuit_breaker_until' => 'datetime',
            'health_score' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}
