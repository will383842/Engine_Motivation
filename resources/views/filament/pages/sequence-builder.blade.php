<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Sequence selector --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Sequence</label>
                    <select wire:model="selectedSequenceId" wire:change="loadSequence"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Choose a sequence --</option>
                        @foreach($this->getSequenceOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($selectedSequenceId)
                    <div class="flex-shrink-0 pt-6 flex gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            v{{ $version }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                            Priority: {{ $priority }}
                        </span>
                    </div>
                @endif
            </div>

            @if($selectedSequenceId)
                <div class="mt-4 grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trigger Event</label>
                        <input type="text" wire:model="triggerEvent"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                               placeholder="e.g. chatter.registered">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority (1-100)</label>
                        <input type="number" wire:model="priority" min="1" max="100"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div class="flex items-end">
                        <button wire:click="saveSequence"
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition">
                            <x-heroicon-s-check class="w-4 h-4 mr-2" />
                            Save Sequence
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Steps --}}
        @if($selectedSequenceId)
            <div class="space-y-3">
                @forelse($steps as $index => $step)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4
                        {{ match($step['type'] ?? 'message') {
                            'message' => 'border-blue-500',
                            'delay' => 'border-yellow-500',
                            'condition' => 'border-purple-500',
                            'ab_split' => 'border-green-500',
                            'action' => 'border-orange-500',
                            'webhook' => 'border-red-500',
                            default => 'border-gray-500',
                        } }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 text-sm font-bold text-gray-600 dark:text-gray-300">
                                    {{ $index }}
                                </span>

                                <select wire:model="steps.{{ $index }}.type"
                                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                    @foreach(\App\Filament\Pages\SequenceBuilderPage::getStepTypes() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                @if(($step['type'] ?? 'message') === 'message')
                                    <input type="text" wire:model="steps.{{ $index }}.template_id"
                                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm w-48"
                                           placeholder="Template slug">
                                @endif

                                @if(in_array($step['type'] ?? '', ['delay', 'message', 'condition']))
                                    <div class="flex items-center gap-1">
                                        <input type="number" wire:model="steps.{{ $index }}.delay_seconds"
                                               class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm w-24"
                                               placeholder="0">
                                        <span class="text-xs text-gray-500">sec</span>
                                    </div>
                                @endif

                                <select wire:model="steps.{{ $index }}.channel"
                                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                    <option value="">Auto</option>
                                    <option value="telegram">Telegram</option>
                                    <option value="whatsapp">WhatsApp</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-1">
                                <button wire:click="moveStepUp({{ $index }})" @class(['opacity-30' => $index === 0])
                                        class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <x-heroicon-s-chevron-up class="w-4 h-4" />
                                </button>
                                <button wire:click="moveStepDown({{ $index }})" @class(['opacity-30' => $index === count($steps) - 1])
                                        class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <x-heroicon-s-chevron-down class="w-4 h-4" />
                                </button>
                                <button wire:click="removeStep({{ $index }})"
                                        class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900 text-red-500">
                                    <x-heroicon-s-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        No steps yet. Click "Add Step" to start building.
                    </div>
                @endforelse
            </div>

            <div class="flex justify-center">
                <button wire:click="addStep"
                        class="inline-flex items-center px-4 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:border-primary-500 hover:text-primary-500 transition">
                    <x-heroicon-s-plus class="w-4 h-4 mr-2" />
                    Add Step
                </button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
