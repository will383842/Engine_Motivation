<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateVariant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TemplateRenderer
{
    /**
     * Render a template for a specific channel and language.
     * Falls back to English if no translation found.
     */
    public function render(string $templateSlug, string $channel, string $language, array $variables = []): ?array
    {
        $template = MessageTemplate::where('slug', $templateSlug)->where('is_active', true)->first();
        if (!$template) {
            return null;
        }

        $variant = $template->variants()
            ->where('channel', $channel)
            ->where('language', $language)
            ->where('is_active', true)
            ->first();

        // Fallback to English
        if (!$variant && $language !== 'en') {
            $variant = $template->variants()
                ->where('channel', $channel)
                ->where('language', 'en')
                ->where('is_active', true)
                ->first();
        }

        // Fallback to French
        if (!$variant && $language !== 'fr') {
            $variant = $template->variants()
                ->where('channel', $channel)
                ->where('language', 'fr')
                ->where('is_active', true)
                ->first();
        }

        // Last resort: any channel with same language
        if (!$variant) {
            $variant = $template->variants()
                ->where('language', $language)
                ->where('is_active', true)
                ->first();
        }

        if (!$variant) {
            Log::warning("No template variant found for {$templateSlug} in any language/channel");
            return null;
        }

        $body = $this->substituteVariables($variant->body, $variables);

        return [
            'body' => $body,
            'subject' => $variant->subject,
            'buttons' => $variant->buttons,
            'media_url' => $variant->media_url,
        ];
    }

    /**
     * Render a template with full chatter context (auto-populates all 12+ variables).
     */
    public function renderForChatter(string $templateSlug, string $channel, Chatter $chatter, array $extraVariables = []): ?array
    {
        $variables = $this->buildChatterVariables($chatter);
        $variables = array_merge($variables, $extraVariables);

        return $this->render($templateSlug, $channel, $chatter->language ?? 'en', $variables);
    }

    /**
     * Build the full variable set for a chatter (12+ standard variables).
     */
    private function buildChatterVariables(Chatter $chatter): array
    {
        $topEarner = $this->getTopEarner();

        return [
            // Core identity
            'name' => $chatter->display_name ?? 'Chatter',

            // Financial
            'earnings' => '$' . number_format(($chatter->lifetime_earnings_cents ?? 0) / 100, 2),
            'balance' => '$' . number_format(($chatter->balance_cents ?? 0) / 100, 2),

            // Gamification
            'rank' => $this->getRank($chatter),
            'streak' => (string) ($chatter->current_streak ?? 0),
            'streak_days' => (string) ($chatter->current_streak ?? 0),
            'level' => (string) ($chatter->level ?? 1),
            'total_xp' => number_format($chatter->total_xp ?? 0),
            'referrals' => (string) ($chatter->extra['referral_count'] ?? 0),

            // Links
            'client_link' => "https://life-expat.com?ref={$chatter->affiliate_code_client}",
            'recruit_link' => "https://life-expat.com/chatter?ref={$chatter->affiliate_code_recruitment}",
            'affiliate_link' => "https://life-expat.com?ref={$chatter->affiliate_code_client}",
            'telegram_link' => 'https://t.me/' . config('telegram.bot_username', 'SOSExpatBot') . "?start={$chatter->affiliate_code_client}",

            // Progression
            'next_level_amount' => '$' . number_format($this->getNextLevelAmount($chatter->level ?? 1) / 100, 2),
            'piggybank' => '$' . number_format(($chatter->extra['piggybank_cents'] ?? 0) / 100, 2),
            'unlock_remaining' => '$' . number_format(max(0, 15000 - ($chatter->lifetime_earnings_cents ?? 0)) / 100, 2),

            // Social proof
            'top_earner' => $topEarner['name'] ?? 'Top Chatter',
            'top_earner_amount' => '$' . number_format(($topEarner['earnings'] ?? 0) / 100, 2),
        ];
    }

    private function substituteVariables(string $body, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $body = str_replace("{{$key}}", (string) $value, $body);
        }
        return $body;
    }

    private function getRank(Chatter $chatter): string
    {
        $rank = Redis::zrevrank('leaderboard:xp:weekly:' . now()->format('Y-W'), $chatter->id);
        return $rank !== null ? (string) ($rank + 1) : '—';
    }

    private function getTopEarner(): array
    {
        $cached = Redis::get('top_earner_weekly');
        if ($cached) {
            return json_decode($cached, true);
        }

        $top = Chatter::where('is_active', true)
            ->orderByDesc('lifetime_earnings_cents')
            ->first();

        $data = [
            'name' => $top ? (mb_substr($top->display_name, 0, 1) . '***') : 'Top Chatter',
            'earnings' => $top?->lifetime_earnings_cents ?? 0,
        ];

        Redis::setex('top_earner_weekly', 3600, json_encode($data));
        return $data;
    }

    private function getNextLevelAmount(int $currentLevel): int
    {
        // XP required formula: round(50 * level^1.8) — converted to approximate cents for display
        $xpRequired = (int) round(50 * pow($currentLevel + 1, 1.8));
        return $xpRequired * 10; // Rough cents equivalent for display
    }
}
