<?php

declare(strict_types=1);

namespace App\Services;

class FeeCalculationService
{
    public function calculateCost(string $countryCode): int
    {
        $costPerCountry = config('whatsapp.cost_per_country', []);
        $costUsd = $costPerCountry[$countryCode] ?? $costPerCountry['default'] ?? 0.05;
        return (int) round($costUsd * 100);
    }

    public function estimateCampaignCost(int $whatsappRecipients, string $defaultCountry = 'default'): int
    {
        return $whatsappRecipients * $this->calculateCost($defaultCountry);
    }
}
