<x-filament-panels::page>
    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Active Numbers</div>
            <div class="text-2xl font-bold text-green-600">{{ $totalActive }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Avg Health Score</div>
            <div class="text-2xl font-bold {{ $avgHealthScore >= 80 ? 'text-green-600' : ($avgHealthScore >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ $avgHealthScore }}
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Open Circuit Breakers</div>
            <div class="text-2xl font-bold {{ $openCircuitBreakers > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $openCircuitBreakers }}
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Budget Spent</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalBudgetCents / 100, 2) }}</div>
        </div>
    </div>

    {{-- Aggregate metrics --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Sent</div>
            <div class="text-xl font-bold text-blue-600">{{ number_format($totalSent) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Delivered</div>
            <div class="text-xl font-bold text-green-600">{{ number_format($totalDelivered) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Blocked</div>
            <div class="text-xl font-bold text-red-600">{{ number_format($totalBlocked) }}</div>
        </div>
    </div>

    {{-- Numbers table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Number</th>
                    <th class="px-4 py-3">Display Name</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Health</th>
                    <th class="px-4 py-3 text-center">Quality</th>
                    <th class="px-4 py-3 text-center">Tier</th>
                    <th class="px-4 py-3 text-center">Circuit Breaker</th>
                    <th class="px-4 py-3 text-right">Sent</th>
                    <th class="px-4 py-3 text-right">Blocked</th>
                    <th class="px-4 py-3 text-right">Daily Limit</th>
                    <th class="px-4 py-3 text-right">Budget Cap</th>
                </tr>
            </thead>
            <tbody>
                @forelse($numbers as $number)
                    <tr class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 font-mono text-xs">{{ $number->phone_number }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $number->display_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($number->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $hs = (float) $number->health_score;
                                $hsColor = $hs >= 80 ? 'text-green-600' : ($hs >= 50 ? 'text-yellow-600' : 'text-red-600');
                            @endphp
                            <span class="font-bold {{ $hsColor }}">{{ $number->health_score }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $qr = $number->quality_rating ?? 'N/A';
                                $qrColor = match($qr) {
                                    'green', 'high' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'yellow', 'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                    'red', 'low' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $qrColor }}">{{ ucfirst($qr) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">{{ $number->current_tier ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $cb = $number->circuit_breaker_state ?? 'closed';
                                $cbColor = match($cb) {
                                    'closed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'half_open', 'half-open' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                    'open' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cbColor }}">{{ ucfirst($cb) }}</span>
                            @if($number->circuit_breaker_until && $number->circuit_breaker_until->isFuture())
                                <div class="text-xs text-gray-400 mt-1">until {{ $number->circuit_breaker_until->format('H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ number_format($number->total_sent) }}</td>
                        <td class="px-4 py-3 text-right {{ $number->total_blocked > 0 ? 'text-red-600 font-semibold' : '' }}">{{ number_format($number->total_blocked) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($number->current_daily_limit ?? 0) }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format(($number->daily_budget_cap_cents ?? 0) / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No WhatsApp numbers configured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
