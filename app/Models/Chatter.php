<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chatter extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'firebase_uid',
        'email',
        'phone',
        'display_name',
        'affiliate_code_client',
        'affiliate_code_recruitment',
        'language',
        'country',
        'timezone',
        'telegram_id',
        'whatsapp_phone',
        'whatsapp_opted_in',
        'preferred_channel',
        'current_streak',
        'longest_streak',
        'total_xp',
        'level',
        'badges_count',
        'balance_cents',
        'lifetime_earnings_cents',
        'total_sales',
        'is_active',
        'last_active_at',
        'last_synced_at',
        'extra',
        'lifecycle_state',
        'lifecycle_changed_at',
        'league_tier',
        'last_whatsapp_number_id',
        'assigned_telegram_bot_id',
    ];

    protected $hidden = ['email', 'phone', 'whatsapp_phone'];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'whatsapp_phone' => 'encrypted',
            'extra' => 'json',
            'is_active' => 'boolean',
            'whatsapp_opted_in' => 'boolean',
            'last_active_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'lifecycle_changed_at' => 'datetime',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'total_xp' => 'integer',
            'total_sales' => 'integer',
            'level' => 'integer',
            'badges_count' => 'integer',
            'balance_cents' => 'integer',
            'lifetime_earnings_cents' => 'integer',
        ];
    }

    public function streak(): HasOne
    {
        return $this->hasOne(Streak::class);
    }

    public function fatigueScores(): HasMany
    {
        return $this->hasMany(ChatterFatigueScore::class);
    }

    public function engagementScore(): HasOne
    {
        return $this->hasOne(ChatterEngagementScore::class);
    }

    public function sendTimeProfile(): HasOne
    {
        return $this->hasOne(ChatterSendTimeProfile::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'chatter_badges')
            ->withPivot('awarded_at')
            ->withTimestamps();
    }

    public function chatterBadges(): HasMany
    {
        return $this->hasMany(ChatterBadge::class);
    }

    public function chatterMissions(): HasMany
    {
        return $this->hasMany(ChatterMission::class);
    }

    /**
     * Alias for chatterMissions() — used by controllers, services, and GDPR export.
     */
    public function missions(): HasMany
    {
        return $this->chatterMissions();
    }

    public function chatterSequences(): HasMany
    {
        return $this->hasMany(ChatterSequence::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ChatterEvent::class);
    }

    public function lifecycleTransitions(): HasMany
    {
        return $this->hasMany(ChatterLifecycleTransition::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function rewardsLedger(): HasMany
    {
        return $this->hasMany(RewardsLedger::class);
    }

    public function suppressionEntries(): HasMany
    {
        return $this->hasMany(SuppressionList::class);
    }

    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function leaderboardEntries(): HasMany
    {
        return $this->hasMany(LeaderboardEntry::class);
    }

    public function healthScores(): HasMany
    {
        return $this->hasMany(ChatterHealthScore::class);
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function abAssignments(): HasMany
    {
        return $this->hasMany(ChatterAbAssignment::class);
    }

    public function npsResponses(): HasMany
    {
        return $this->hasMany(NpsResponse::class);
    }

    public function fraudFlags(): HasMany
    {
        return $this->hasMany(FraudFlag::class);
    }

    public function revenueAttributions(): HasMany
    {
        return $this->hasMany(RevenueAttribution::class);
    }

    /**
     * Alias for events() — used by EngagementScoreService, FraudDetectionService.
     */
    public function chatterEvents(): HasMany
    {
        return $this->events();
    }

    /**
     * Check if chatter is opted in for a given channel.
     */
    public function isOptedIn(string $channel): bool
    {
        return match ($channel) {
            'telegram' => !empty($this->telegram_id),
            'whatsapp' => !empty($this->whatsapp_phone),
            'dashboard' => true,
            default => false,
        };
    }
}
