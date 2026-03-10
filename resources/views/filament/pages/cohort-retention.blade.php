<x-filament-panels::page>
    @if($retentionData->isEmpty())
        <div class="text-center py-12">
            <x-heroicon-o-chart-bar-square class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No cohort data yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cohort data will appear once chatters register.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Cohort</th>
                        <th class="px-4 py-3 text-right">Registered</th>
                        @foreach($periods as $days)
                            <th class="px-4 py-3 text-right">D+{{ $days }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($retentionData as $cohort)
                        <tr class="border-b dark:border-gray-600">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $cohort['month'] }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($cohort['total']) }}</td>
                            @foreach($periods as $days)
                                @php
                                    $value = $cohort['retention'][$days];
                                @endphp
                                <td class="px-4 py-3 text-right">
                                    @if($value === null)
                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                    @else
                                        @php
                                            // Heatmap coloring: green (high) to red (low)
                                            if ($value >= 70) $bgClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                                            elseif ($value >= 50) $bgClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300';
                                            elseif ($value >= 30) $bgClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                                            elseif ($value >= 15) $bgClass = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
                                            else $bgClass = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
                                        @endphp
                                        <span class="inline-flex items-center justify-center px-2 py-1 rounded text-xs font-semibold {{ $bgClass }}" style="min-width: 3rem;">
                                            {{ $value }}%
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>Legend:</span>
            <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">70%+</span>
            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">50-69%</span>
            <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">30-49%</span>
            <span class="px-2 py-0.5 rounded bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">15-29%</span>
            <span class="px-2 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">&lt;15%</span>
        </div>
    @endif
</x-filament-panels::page>
