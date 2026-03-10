<?php

namespace App\Providers;

use App\Models\Badge;
use App\Models\Campaign;
use App\Models\Chatter;
use App\Models\Mission;
use App\Models\Sequence;
use App\Listeners\AwardBadges;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-audit all admin-managed models
        $auditedModels = [Chatter::class, Campaign::class, Sequence::class, Mission::class, Badge::class];
        foreach ($auditedModels as $model) {
            $model::observe(AuditObserver::class);
        }

        // Register event subscribers (subscribe() not auto-discovered in Laravel 11)
        Event::subscribe(AwardBadges::class);
    }
}
