<?php

use Illuminate\Support\Facades\Schedule;

// ─── Gamification ───
Schedule::command('streaks:check')->dailyAt('00:00')->onOneServer();
Schedule::command('leaderboards:refresh')->everyFifteenMinutes()->onOneServer();
Schedule::command('leagues:process')->weeklyOn(1, '00:00')->onOneServer(); // Monday

// ─── Lifecycle & Engagement ───
Schedule::command('lifecycle:process')->dailyAt('04:00')->onOneServer();
Schedule::command('fatigue:calculate')->everySixHours()->onOneServer();
Schedule::command('engagement:calculate')->everySixHours()->onOneServer();

// ─── Reconciliation & Monitoring ───
Schedule::command('chatters:reconcile')->dailyAt('04:00')->onOneServer();
Schedule::command('whatsapp:monitor')->everyFourHours()->onOneServer();
Schedule::command('pool:monitor --channel=whatsapp')->everyFourHours()->onOneServer();
Schedule::command('pool:monitor --channel=telegram')->hourly()->onOneServer();

// ─── Sequences & Segments ───
Schedule::command('sequences:advance')->everyMinute()->onOneServer()->withoutOverlapping();
Schedule::command('segments:refresh')->everyThirtyMinutes()->onOneServer();

// ─── Webhook catchup (retry pending events) ───
Schedule::command('webhooks:catchup')->everyFiveMinutes()->onOneServer();

// ─── Maintenance ───
Schedule::command('partitions:create')->monthlyOn(25, '03:00')->onOneServer();
Schedule::command('data:purge')->monthlyOn(1, '03:00')->onOneServer();
Schedule::command('backup:test')->cron('0 5 1-7 * 0')->onOneServer(); // 1st Sunday 05:00
