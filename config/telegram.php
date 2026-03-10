<?php
return [
    "bot_token" => env("TELEGRAM_BOT_TOKEN"),
    "webhook_secret" => env("TELEGRAM_WEBHOOK_SECRET"),
    "admin_chat_id" => env("TELEGRAM_ADMIN_CHAT_ID"),
    "rate_limits" => ["per_chat_per_second" => 1, "global_per_second" => 30, "group_per_minute" => 20],
    "retry" => ["max_attempts" => 3, "backoff" => [1, 5, 30]],
];
