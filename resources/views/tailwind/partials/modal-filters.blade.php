@if ($showFiltersModal && count($filterDefs) > 0)
    <div
        class="fixed inset-0 bg-gray-500/75 flex items-center justify-center z-50"
        @keydown.escape.window="$wire.set('showFiltersModal', false)"
    >
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h5 class="font-semibold text-gray-900 text-base">Filtry</h5>
                <button
                    type="button"
                    wire:click="$set('showFiltersModal', false)"
                    class="text-gray-400 hover:text-gray-600 focus:outline-none transition-colors"
                    title="Zamknij"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="flex flex-col gap-3">
                @foreach ($filterDefs as $filter)
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            {{ $filter->label }}
                        </label>

                        @if ($filter->type->value === 'select')
                            <select
                                wire:model="activeFilters.{{ $filter->key }}"
                                class="block w-full rounded-md border-0 py-1 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">— Wszystkie —</option>
                                @foreach ($filter->options as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>

                        @elseif ($filter->type->value === 'boolean')
                            <select
                                wire:model="activeFilters.{{ $filter->key }}"
                                class="block w-full rounded-md border-0 py-1 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">— Wszystkie —</option>
                                <option value="1">Tak</option>
                                <option value="0">Nie</option>
                            </select>

                        @elseif ($filter->type->value === 'date')
                            <input
                                type="date"
                                wire:model="activeFilters.{{ $filter->key }}"
                                class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                            >

                        @elseif ($filter->type->value === 'date_range')
                            <div class="flex items-center gap-2">
                                <input
                                    type="date"
                                    wire:model="activeFilters.{{ $filter->key }}.from"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                    placeholder="Od"
                                >
                                <span class="text-gray-400 text-sm">–</span>
                                <input
                                    type="date"
                                    wire:model="activeFilters.{{ $filter->key }}.to"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                    placeholder="Do"
                                >
                            </div>

                        @elseif ($filter->type->value === 'datetime')
                            <input
                                type="datetime-local"
                                wire:model="activeFilters.{{ $filter->key }}"
                                class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                            >

                        @elseif ($filter->type->value === 'datetime_range')
                            <div class="flex items-center gap-2">
                                <input
                                    type="datetime-local"
                                    wire:model="activeFilters.{{ $filter->key }}.from"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                    placeholder="Od"
                                >
                                <span class="text-gray-400 text-sm">–</span>
                                <input
                                    type="datetime-local"
                                    wire:model="activeFilters.{{ $filter->key }}.to"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                    placeholder="Do"
                                >
                            </div>

                        @elseif ($filter->type->value === 'time')
                            <input
                                type="time"
                                wire:model="activeFilters.{{ $filter->key }}"
                                class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                            >

                        @elseif ($filter->type->value === 'number')
                            <input
                                type="number"
                                wire:model="activeFilters.{{ $filter->key }}"
                                placeholder="Filtruj po {{ strtolower($filter->label) }}..."
                                class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                            >

                        @elseif ($filter->type->value === 'number_range')
                            <div class="flex items-center gap-2">
                                <input
                                    type="number"
                                    wire:model="activeFilters.{{ $filter->key }}.from"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                    placeholder="Min"
                                >
                                <span class="text-gray-400 text-sm">–</span>
                                <input
                                    type="number"
                                    wire:model="activeFilters.{{ $filter->key }}.to"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                    placeholder="Max"
                                >
                            </div>

                        @elseif ($filter->type->value === 'money')
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    wire:model="activeFilters.{{ $filter->key }}.from"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                    placeholder="Od (np. 1 000,00)"
                                >
                                <span class="text-gray-400 text-sm">–</span>
                                <input
                                    type="text"
                                    wire:model="activeFilters.{{ $filter->key }}.to"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                    placeholder="Do (np. 5 000,00)"
                                >
                            </div>

                        @else
                            <input
                                type="text"
                                wire:model="activeFilters.{{ $filter->key }}"
                                placeholder="Filtruj po {{ strtolower($filter->label) }}..."
                                class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                            >
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-3 mt-4">
                <button type="button" wire:click="clearFilters"
                    class="text-sm text-gray-500 underline hover:text-gray-700 focus:outline-none transition-colors">
                    Wyczyść filtry
                </button>
                <button type="button" wire:click="applyActiveFilters"
                    class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-medium rounded-md bg-indigo-500 text-white hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    Zastosuj
                </button>
            </div>
        </div>
    </div>
@endif
