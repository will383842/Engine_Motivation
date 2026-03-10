<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TwilioWebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ChatterStatsController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Middleware\VerifyFirebaseWebhookSignature;
use App\Http\Middleware\WebhookIdempotency;
use App\Http\Middleware\VerifyTwilioSignature;
use Illuminate\Support\Facades\Route;

// Health check (no auth)
Route::get('/health', HealthController::class);

// Firebase webhook (HMAC + idempotency)
Route::post('/webhook', [WebhookController::class, 'handle'])
    ->middleware([VerifyFirebaseWebhookSignature::class, WebhookIdempotency::class]);

// Twilio WhatsApp status callbacks
Route::post('/webhooks/twilio/status', [TwilioWebhookController::class, 'handleStatus'])
    ->middleware(VerifyTwilioSignature::class)
    ->name('twilio.status');

// Telegram bot webhook
Route::post('/webhooks/telegram/{botToken}', [TelegramWebhookController::class, 'handle']);

// Link tracking (public, no auth)
Route::get('/t/{messageLogId}/{linkHash}', [TrackingController::class, 'click'])->name('tracking.click');

// Public API (no auth for leaderboard display)
Route::get('/leaderboard/{type}/{period}', [LeaderboardController::class, 'show'])
    ->where('type', 'xp|revenue|conversions|referrals|streaks|sales|streak|engagement')
    ->where('period', 'weekly|monthly|alltime');

// Authenticated API
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
    Route::get('/leaderboard/weekly', [LeaderboardController::class, 'weekly']);
    Route::get('/leaderboard/monthly', [LeaderboardController::class, 'monthly']);
    Route::get('/chatter/{uid}/stats', [ChatterStatsController::class, 'show']);
    Route::get('/chatter/{uid}/streaks', [ChatterStatsController::class, 'streaks']);
    Route::get('/chatter/{uid}/missions', [ChatterStatsController::class, 'missions']);
});
