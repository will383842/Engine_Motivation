<x-filament-widgets::widget>
    <x-filament::section heading="WhatsApp Number Pool">
        @if($numbers->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No WhatsApp numbers configured.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <tr>
                            <th class="px-3 py-2">Number</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Health</th>
                            <th class="px-3 py-2">Sent Today</th>
                            <th class="px-3 py-2">Daily Limit</th>
                            <th class="px-3 py-2">Quality</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($numbers as $number)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-3 py-2 font-mono">{{ $number->display_name ?? $number->phone_number }}</td>
                                <td class="px-3 py-2">
                                    @if($number->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                                    @endif
                                    @if($number->circuit_breaker_state === 'open')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 ml-1">CB Open</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @php
                                        $health = (float) $number->health_score;
                                        $healthColor = $health >= 80 ? 'text-green-600' : ($health >= 50 ? 'text-yellow-600' : 'text-red-600');
                                    @endphp
                                    <span class="{{ $healthColor }} font-semibold">{{ number_format($health, 0) }}%</span>
                                </td>
                                <td class="px-3 py-2">{{ number_format($number->sent_today) }}</td>
                                <td class="px-3 py-2">{{ number_format($number->daily_limit ?? 0) }}</td>
                                <td class="px-3 py-2">{{ $number->quality_rating ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
