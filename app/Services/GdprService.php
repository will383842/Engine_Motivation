<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\ConsentRecord;
use App\Models\SuppressionList;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GdprService
{
    public function exportData(Chatter $chatter): array
    {
        return [
            'personal_data' => [
                'display_name' => $chatter->display_name,
                'email' => $chatter->email,
                'phone' => $chatter->phone,
                'language' => $chatter->language,
                'country' => $chatter->country,
                'timezone' => $chatter->timezone,
                'created_at' => $chatter->created_at?->toISOString(),
            ],
            'gamification' => [
                'level' => $chatter->level,
                'xp' => $chatter->total_xp,
                'streak' => $chatter->current_streak,
                'badges' => $chatter->badges()->pluck('slug')->toArray(),
            ],
            'missions' => $chatter->missions()->with('mission')->get()->toArray(),
            'message_history' => $chatter->messageLogs()->limit(1000)->get()->toArray(),
            'consent_records' => $chatter->consentRecords()->get()->toArray(),
        ];
    }

    public function anonymize(Chatter $chatter, string $reason = 'gdpr_erasure'): void
    {
        DB::transaction(function () use ($chatter, $reason) {
            $chatter->update([
                'email' => null,
                'phone' => null,
                'email_hash' => null,
                'whatsapp_phone' => null,
                'display_name' => 'Anonymized User',
                'telegram_id' => null,
                'is_active' => false,
                'lifecycle_state' => 'sunset',
                'extra' => '{}',
            ]);

            SuppressionList::create([
                'chatter_id' => $chatter->id,
                'channel' => 'all',
                'reason' => $reason,
                'source' => 'gdpr',
            ]);

            // Stop all active sequences
            $chatter->chatterSequences()
                ->where('status', 'active')
                ->update(['status' => 'exited', 'exit_reason' => 'gdpr_erasure']);

            // Remove from all Redis leaderboards
            $this->purgeFromLeaderboards($chatter);

            // Anonymize rewards_ledger
            $chatter->rewardsLedger()->update([
                'description' => 'GDPR anonymized',
            ]);

            // Anonymize message_logs body content
            $chatter->messageLogs()->update([
                'body' => '[GDPR ERASED]',
                'metadata' => null,
            ]);

            // Revoke all active consent records
            $chatter->consentRecords()
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            // Create a consent record for the erasure itself
            ConsentRecord::create([
                'chatter_id' => $chatter->id,
                'consent_type' => 'gdpr_erasure',
                'granted' => false,
                'consent_text' => 'Right to erasure exercised (Art. 17 GDPR)',
                'version' => '1.0',
            ]);

            Log::info("GDPR anonymization completed for chatter {$chatter->id}");
        });
    }

    public function recordConsent(Chatter $chatter, string $type, string $channel, string $consentText, string $version, ?string $ip = null): void
    {
        ConsentRecord::create([
            'chatter_id' => $chatter->id,
            'consent_type' => $type,
            'granted' => true,
            'ip_address' => $ip,
            'consent_text' => $consentText,
            'version' => $version,
        ]);
    }

    /**
     * Remove chatter from all Redis leaderboard sorted sets.
     */
    private function purgeFromLeaderboards(Chatter $chatter): void
    {
        $categories = LeaderboardService::CATEGORIES;
        $patterns = ['weekly:*', 'monthly:*', 'alltime', 'country:*'];

        foreach ($categories as $category) {
            // Remove from alltime
            Redis::zrem("leaderboard:{$category}:alltime", $chatter->id);

            // Remove from weekly/monthly (scan pattern)
            foreach (['weekly', 'monthly'] as $period) {
                $cursor = '0';
                do {
                    [$cursor, $keys] = Redis::scan($cursor, [
                        'match' => "leaderboard:{$category}:{$period}:*",
                        'count' => 100,
                    ]);
                    foreach ($keys ?? [] as $key) {
                        Redis::zrem($key, $chatter->id);
                    }
                } while ($cursor !== '0');
            }
        }

        // Also remove streak/daily tracking keys
        $cursor = '0';
        do {
            [$cursor, $keys] = Redis::scan($cursor, [
                'match' => "*:{$chatter->id}:*",
                'count' => 100,
            ]);
            foreach ($keys ?? [] as $key) {
                Redis::del($key);
            }
        } while ($cursor !== '0');
    }

    public function revokeConsent(Chatter $chatter, string $type): void
    {
        ConsentRecord::where('chatter_id', $chatter->id)
            ->where('consent_type', $type)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
