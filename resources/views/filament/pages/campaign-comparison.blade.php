<x-filament-panels::page>
    @if($comparisons->isEmpty())
        <div class="text-center py-12">
            <x-heroicon-o-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No campaigns to compare</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Campaigns will appear here once they have been sent.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Campaign</th>
                        <th class="px-4 py-3">Channel</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Recipients</th>
                        <th class="px-4 py-3 text-right">Sent</th>
                        <th class="px-4 py-3 text-right">Delivered</th>
                        <th class="px-4 py-3 text-right">Read</th>
                        <th class="px-4 py-3 text-right">Failed</th>
                        <th class="px-4 py-3 text-right">Delivery %</th>
                        <th class="px-4 py-3 text-right">Read %</th>
                        <th class="px-4 py-3 text-right">Fail %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparisons as $c)
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $c['name'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $c['channel'] === 'whatsapp' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $c['channel'] === 'telegram' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $c['channel'] === 'email' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                    {{ !in_array($c['channel'], ['whatsapp','telegram','email']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' : '' }}
                                ">
                                    {{ ucfirst($c['channel'] ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $c['scheduled_at'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($c['total_recipients']) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($c['sent']) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($c['delivered']) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($c['read']) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($c['failed']) }}</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $c['delivery_rate'] >= 90 ? 'text-green-600' : ($c['delivery_rate'] >= 70 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $c['delivery_rate'] }}%
                            </td>
                            <td class="px-4 py-3 text-right font-semibold {{ $c['read_rate'] >= 50 ? 'text-green-600' : ($c['read_rate'] >= 25 ? 'text-yellow-600' : 'text-gray-500') }}">
                                {{ $c['read_rate'] }}%
                            </td>
                            <td class="px-4 py-3 text-right font-semibold {{ $c['fail_rate'] <= 5 ? 'text-green-600' : ($c['fail_rate'] <= 15 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $c['fail_rate'] }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
