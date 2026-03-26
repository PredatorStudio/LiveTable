<div class="flex flex-wrap items-center gap-2">

    {{-- Column visibility toggle --}}
    @if ($displayColumnList)
        <div class="relative" @click.outside="showColumnPanel = false">
            <button
                type="button"
                @click="showColumnPanel = !showColumnPanel"
                title="Widoczność kolumn"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                Kolumny
            </button>

            <div
                x-show="showColumnPanel"
                x-transition
                class="absolute right-0 mt-1 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-30"
                style="width: 15rem;"
            >
                <p class="border-b border-gray-200 px-3 py-2 text-xs text-gray-500">
                    Przeciągnij, by zmienić kolejność
                </p>
                <ul class="p-1 m-0 list-none">
                    @foreach ($allColumns as $col)
                        <li
                            class="flex items-center gap-2 rounded px-2 py-1 text-sm transition-colors"
                            :class="dragOverCol === '{{ $col->key }}' ? 'bg-indigo-50' : 'text-gray-700'"
                            style="cursor: grab;"
                            draggable="true"
                            @dragstart="startDrag('{{ $col->key }}')"
                            @dragover.prevent="onDragOver('{{ $col->key }}')"
                            @drop.prevent="onDrop('{{ $col->key }}')"
                            @dragend="draggingCol = null; dragOverCol = null"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>

                            <span class="flex-1 truncate">{{ $col->label }}</span>

                            <button
                                type="button"
                                wire:click="toggleColumn('{{ $col->key }}')"
                                @click.stop
                                class="text-gray-400 hover:text-gray-600 focus:outline-none"
                                title="{{ in_array($col->key, $hiddenColumns) ? 'Pokaż kolumnę' : 'Ukryj kolumnę' }}"
                            >
                                @if (in_array($col->key, $hiddenColumns))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Filters button --}}
    @if (count($filterDefs) > 0)
        @php $activeFilterCount = count(array_filter($activeFilters, fn($v) => $v !== '' && $v !== null)); @endphp
        <button
            type="button"
            wire:click="$set('showFiltersModal', true)"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors {{ $activeFilterCount > 0 ? 'bg-indigo-500 text-white hover:bg-indigo-600' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filtry
            @if ($activeFilterCount > 0)
                <span class="ml-1 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium bg-white text-indigo-500">
                    {{ $activeFilterCount }}
                </span>
            @endif
        </button>
    @endif

    {{-- Search --}}
    @if ($displaySearch)
        <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 absolute text-gray-400 pointer-events-none" style="left: 0.6rem; top: 50%; transform: translateY(-50%);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Szukaj..."
                class="block rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                style="padding-left: 2rem; width: 13rem;"
            >
        </div>
    @endif

    {{-- Default creating button --}}
    @if ($canCreate)
        <button
            type="button"
            wire:click="openCreatingModal"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md bg-indigo-500 text-white hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Dodaj rekord
        </button>
    @endif

    {{-- Header action buttons --}}
    @foreach ($headerActionDefs as $action)
        @if ($action->href)
            <a
                href="{{ $action->href }}"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            >
                @if ($action->icon){!! $action->icon !!}@endif
                {{ $action->label }}
            </a>
        @else
            <button
                type="button"
                wire:click="{{ $action->method }}"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            >
                @if ($action->icon){!! $action->icon !!}@endif
                {{ $action->label }}
            </button>
        @endif
    @endforeach

</div>
