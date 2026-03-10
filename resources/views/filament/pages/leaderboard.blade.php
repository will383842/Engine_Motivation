<x-filament-panels::page>
    {{-- Period and category selectors --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <div class="flex gap-1 items-center">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">Period:</span>
            @foreach(['weekly' => 'Weekly', 'monthly' => 'Monthly', 'alltime' => 'All Time'] as $key => $label)
                <button
                    wire:click="setPeriod('{{ $key }}')"
                    class="px-3 py-1.5 text-sm rounded-lg font-medium transition
                        {{ $period === $key
                            ? 'bg-primary-500 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex gap-1 items-center ml-4">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">Category:</span>
            @foreach($categories as $cat)
                <button
                    wire:click="setCategory('{{ $cat }}')"
                    class="px-3 py-1.5 text-sm rounded-lg font-medium transition
                        {{ $category === $cat
                            ? 'bg-primary-500 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}"
                >
                    {{ ucfirst($cat) }}
                </button>
            @endforeach
        </div>
    </div>

    @if($entries->isEmpty())
        <div class="text-center py-12">
            <x-heroicon-o-trophy class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No leaderboard data</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Leaderboard entries will appear as chatters earn scores.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 w-16 text-center">#</th>
                        <th class="px-4 py-3">Chatter</th>
                        <th class="px-4 py-3">Country</th>
                        <th class="px-4 py-3 text-center">Level</th>
                        <th class="px-4 py-3 text-right">Score</th>
                        <th class="px-4 py-3 text-right">Total XP</th>
                        <th class="px-4 py-3 text-center">Streak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700
                            {{ $entry['rank'] <= 3 ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}">
                            <td class="px-4 py-3 text-center">
                                @if($entry['rank'] === 1)
                                    <span class="text-lg">&#129351;</span>
                                @elseif($entry['rank'] === 2)
                                    <span class="text-lg">&#129352;</span>
                                @elseif($entry['rank'] === 3)
                                    <span class="text-lg">&#129353;</span>
                                @else
                                    <span class="text-gray-500">{{ $entry['rank'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $entry['display_name'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $entry['country'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    Lv.{{ $entry['level'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-primary-600 dark:text-primary-400">
                                {{ number_format($entry['score']) }}
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($entry['total_xp']) }}</td>
                            <td class="px-4 py-3 text-center">{{ $entry['current_streak'] }} days</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
