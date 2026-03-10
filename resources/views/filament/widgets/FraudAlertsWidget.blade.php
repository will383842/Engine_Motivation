<x-filament-widgets::widget>
    <x-filament::section heading="Latest Fraud Alerts">
        @if($alerts->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No unresolved high-severity fraud alerts.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <tr>
                            <th class="px-3 py-2">Chatter</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Severity</th>
                            <th class="px-3 py-2">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-3 py-2">{{ $alert->chatter?->display_name ?? 'Unknown' }}</td>
                                <td class="px-3 py-2">{{ str_replace('_', ' ', $alert->flag_type) }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        {{ ucfirst($alert->severity) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-500">{{ $alert->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
