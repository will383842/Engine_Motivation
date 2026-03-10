<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Recent Activity Feed --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h3>

            @php
                $recentEvents = \App\Models\ChatterEvent::with('chatter')
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
            @endphp

            @if($recentEvents->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 text-sm">No recent activity yet.</p>
            @else
                <div class="space-y-3">
                    @foreach($recentEvents as $event)
                        <div class="flex items-center gap-3 text-sm">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                @switch($event->event_type)
                                    @case('chatter.registered') bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 @break
                                    @case('chatter.sale_completed') bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 @break
                                    @case('chatter.first_sale') bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300 @break
                                    @case('chatter.telegram_linked') bg-cyan-100 text-cyan-700 dark:bg-cyan-900 dark:text-cyan-300 @break
                                    @case('chatter.level_up') bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 @break
                                    @case('chatter.withdrawal') bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 @break
                                    @default bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                @endswitch
                            ">
                                {{ str_replace('chatter.', '', $event->event_type) }}
                            </span>
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ $event->chatter?->display_name ?? 'Unknown' }}
                            </span>
                            <span class="text-gray-400 dark:text-gray-500 ml-auto">
                                {{ $event->created_at->diffForHumans() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Quick Stats Row --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @php
                $totalChatters = \App\Models\Chatter::count();
                $totalBadgesAwarded = \App\Models\ChatterBadge::count();
                $totalMessages = \App\Models\MessageLog::count();
                $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
                $pendingWebhooks = \App\Models\WebhookEvent::where('status', 'pending')->count();
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalChatters) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Chatters</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalBadgesAwarded) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Badges Awarded</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalMessages) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Messages Sent</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold {{ $failedJobs > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">{{ $failedJobs }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Failed Jobs</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold {{ $pendingWebhooks > 10 ? 'text-yellow-600' : 'text-gray-900 dark:text-white' }}">{{ $pendingWebhooks }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pending Webhooks</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
