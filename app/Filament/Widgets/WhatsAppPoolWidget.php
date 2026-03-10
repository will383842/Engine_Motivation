<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\WhatsAppNumber;
use App\Models\MessageLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class WhatsAppPoolWidget extends Widget
{
    protected static string $view = 'filament.widgets.WhatsAppPoolWidget';
    protected static ?string $heading = 'WhatsApp Number Pool';

    protected function getViewData(): array
    {
        $numbers = WhatsAppNumber::orderByDesc('is_active')
            ->orderByDesc('health_score')
            ->get()
            ->map(function (WhatsAppNumber $number) {
                $sentToday = 0;

                try {
                    $redisKey = 'whatsapp:sent_today:' . $number->id;
                    $sentToday = (int) Redis::get($redisKey);
                } catch (\Throwable) {
                    // Redis unavailable — fall back to DB count
                    $sentToday = MessageLog::where('sender_id', $number->id)
                        ->where('sender_type', 'whatsapp_number')
                        ->where('channel', 'whatsapp')
                        ->whereDate('sent_at', Carbon::today())
                        ->count();
                }

                $number->sent_today = $sentToday;

                return $number;
            });

        return [
            'numbers' => $numbers,
        ];
    }
}
