<div
    x-data="{
        showColumnPanel: false,
        draggingCol: null,
        dragOverCol: null,
        startDrag(key) {
            this.draggingCol = key;
        },
        onDragOver(key) {
            if (this.draggingCol !== key) this.dragOverCol = key;
        },
        onDrop(key) {
            if (this.draggingCol && this.draggingCol !== key) {
                const order = JSON.parse(JSON.stringify($wire.columnOrder));
                const from  = order.indexOf(this.draggingCol);
                const to    = order.indexOf(key);
                if (from !== -1 && to !== -1) {
                    order.splice(to, 0, order.splice(from, 1)[0]);
                    $wire.reorderColumns(order);
                }
            }
            this.draggingCol = null;
            this.dragOverCol = null;
        }
    }"
    class="flex flex-col gap-3"
>

    {{-- Top bar --}}
    <div class="flex flex-wrap items-center justify-between gap-2">

        {{-- Left: bulk actions + select-page + export --}}
        <div class="flex items-center gap-2">
            @if ($selectable && count($selected) > 0)
                <span class="text-sm text-gray-500">
                    {{ count($selected) }} zaznaczonych
                </span>
                @foreach ($bulkActionDefs as $action)
                    <button
                        type="button"
                        wire:click="{{ $action->method }}"
                        title="{{ $action->label }}"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    >
                        @if ($action->icon)
                            {!! $action->icon !!}
                        @else
                            {{ mb_substr($action->label, 0, 1) }}
                        @endif
                    </button>
                @endforeach
            @endif

            {{-- Zaznacz stronę (tylko gdy selectable i brak selectAllQuery) --}}
            @if ($selectable && !$selectAllQuery)
                <button
                    type="button"
                    wire:click="selectRows({{ json_encode($currentPageIds) }})"
                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                >
                    Zaznacz stronę
                </button>
            @endif

            {{-- Eksport CSV --}}
            @if ($exportCsv)
                <button
                    type="button"
                    wire:click="exportCsv"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    title="Eksportuj do CSV"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    CSV
                </button>
            @endif

            {{-- Eksport PDF --}}
            @if ($exportPdf)
                <button
                    type="button"
                    wire:click="exportPdf"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    title="Eksportuj do PDF"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    PDF
                </button>
            @endif

            {{-- Mass delete --}}
            @if ($massDeleteEnabled && $selectable)
                <button
                    type="button"
                    wire:click="massDelete"
                    wire:confirm="Czy na pewno chcesz usunąć zaznaczone wiersze?"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border border-red-300 bg-white text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                    title="Usuń zaznaczone"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    Usuń
                </button>
            @endif
        </div>

        {{-- Right side controls --}}
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

                    {{-- Dropdown panel --}}
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
                                    {{-- drag handle --}}
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
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Filters button (only when filters are defined) --}}
            @if (count($filterDefs) > 0)
                @php $activeFilterCount = count(array_filter($activeFilters, fn($v) => $v !== '' && $v !== null)); @endphp
                <button
                    type="button"
                    wire:click="$set('showFiltersModal', true)"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors {{ $activeFilterCount > 0 ? 'bg-indigo-600 text-white hover:bg-indigo-500' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Filtry
                    @if ($activeFilterCount > 0)
                        <span class="ml-1 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium bg-white text-indigo-600">
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
                        class="block rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                        style="padding-left: 2rem; width: 13rem;"
                    >
                </div>
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
    </div>

    {{-- Active filter tags --}}
    @php
        $activeFilterDefs = collect($filterDefs)->keyBy('key');
        $displayedFilters = collect($activeFilters)->filter(fn($v) => $v !== '' && $v !== null);
    @endphp
    @if ($displayedFilters->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($displayedFilters as $key => $value)
                @php
                    $filterDef    = $activeFilterDefs->get($key);
                    $filterLabel  = $filterDef ? $filterDef->label : $key;
                    $displayValue = ($filterDef && $filterDef->type === 'select' && isset($filterDef->options[$value]))
                        ? $filterDef->options[$value]
                        : $value;
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 border border-indigo-200 px-2.5 py-1 text-xs font-medium text-indigo-700">
                    <span>{{ $filterLabel }}: {{ $displayValue }}</span>
                    <button
                        type="button"
                        wire:click="removeFilter('{{ $key }}')"
                        class="flex-shrink-0 rounded-full p-0.5 text-indigo-400 hover:text-indigo-600 hover:bg-indigo-100 focus:outline-none transition-colors"
                        title="Usuń filtr"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </span>
            @endforeach

            @if ($displayedFilters->count() > 1)
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="text-xs text-gray-400 underline hover:text-gray-600 focus:outline-none transition-colors"
                >
                    Wyczyść wszystkie
                </button>
            @endif
        </div>
    @endif

    {{-- Gmail-style select-all banner --}}
    @if ($selectAllQuery)
        <div class="flex items-center justify-between gap-3 rounded-md bg-blue-50 border border-blue-200 px-4 py-2 text-sm text-blue-700">
            <span>Zaznaczono wszystkie <strong>{{ $total }}</strong> wiersze z wyników.</span>
            <button
                type="button"
                wire:click="clearSelectAllQuery"
                class="text-xs font-medium text-blue-600 hover:text-blue-800 underline focus:outline-none transition-colors"
            >
                Wyczyść zaznaczenie
            </button>
        </div>
    @elseif ($selectable && $allPageSelected && $total > count($currentPageIds))
        <div class="flex items-center justify-between gap-3 rounded-md bg-blue-50 border border-blue-200 px-4 py-2 text-sm text-blue-700">
            <span>Zaznaczono <strong>{{ count($selected) }}</strong> wierszy z tej strony.</span>
            <button
                type="button"
                wire:click="selectAllFromQuery"
                class="text-xs font-medium text-blue-600 hover:text-blue-800 underline focus:outline-none transition-colors"
            >
                Zaznacz wszystkie {{ $total }} wiersze z wyników →
            </button>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-auto border border-gray-200 rounded-lg" style="max-height: 75vh;">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    {{-- Expand column header --}}
                    @if ($expandable)
                        <th style="width: 28px; min-width: 28px;"></th>
                    @endif

                    {{-- Select-all checkbox (only in checkbox mode) --}}
                    @if ($hasCheckboxCol)
                        <th class="px-3 py-2" style="width: 2.5rem;">
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer"
                                :checked="{{ json_encode($currentPageIds) }}.length > 0
                                    && {{ json_encode($currentPageIds) }}.every(id => $wire.selected.includes(id))"
                                @change="$event.target.checked
                                    ? $wire.selectRows({{ json_encode($currentPageIds) }})
                                    : $wire.deselectRows({{ json_encode($currentPageIds) }})"
                            >
                        </th>
                    @endif

                    {{-- Column headers (draggable + sortable) --}}
                    @foreach ($visibleColumns as $col)
                        <th
                            class="px-3 py-2 text-xs font-semibold text-gray-500 select-none transition-colors"
                            :class="dragOverCol === '{{ $col->key }}' ? 'bg-indigo-50' : ''"
                            style="cursor: {{ $col->sortable ? 'pointer' : 'grab' }}; white-space: nowrap;"
                            draggable="true"
                            @dragstart="startDrag('{{ $col->key }}')"
                            @dragover.prevent="onDragOver('{{ $col->key }}')"
                            @drop.prevent="onDrop('{{ $col->key }}')"
                            @dragend="draggingCol = null; dragOverCol = null"
                            @if ($col->sortable) wire:click="sort('{{ $col->key }}')" @endif
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ $col->label }}
                                @if ($col->sortable)
                                    @if ($sortBy === $col->key)
                                        @if ($sortDir === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                        @endif
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                                    @endif
                                @endif
                            </span>
                        </th>
                    @endforeach

                    {{-- Row actions column header --}}
                    @if ($hasRowActions)
                        <th class="px-3 py-2" style="width: 60px; min-width: 60px;"></th>
                    @endif
                </tr>
            </thead>

            @forelse ($items as $row)
                @php $rowId = (string) data_get($row, $primaryKey); @endphp
                <tbody class="bg-white divide-y divide-gray-100" x-data="{ open: false }">
                    {{-- Main row --}}
                    <tr
                        :class="$wire.selected.includes('{{ $rowId }}') ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                        @if ($isRowSelectMode)
                            wire:click="toggleSelectRow('{{ $rowId }}')"
                            style="cursor: pointer;"
                        @endif
                    >
                        {{-- Expand toggle --}}
                        @if ($expandable)
                            <td style="width: 28px; min-width: 28px;" class="text-center align-middle px-1">
                                @if (count($subRowsMap[$rowId] ?? []) > 0)
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="text-gray-400 hover:text-gray-600 focus:outline-none"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3"
                                             fill="currentColor" viewBox="0 0 16 16"
                                             :style="open ? 'transform:rotate(90deg)' : ''"
                                             style="transition:transform .15s ease; display:block;">
                                            <path d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                    </button>
                                @endif
                            </td>
                        @endif

                        @if ($hasCheckboxCol)
                            <td class="px-3 py-2" style="width: 2.5rem;">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer"
                                    :checked="$wire.selected.includes('{{ $rowId }}')"
                                    @change="$wire.toggleSelectRow('{{ $rowId }}')"
                                >
                            </td>
                        @endif

                        @foreach ($visibleColumns as $col)
                            <td class="px-3 py-2 text-sm text-gray-700 align-middle">{!! $col->renderCell($row, $primaryKey) !!}</td>
                        @endforeach

                        {{-- Row actions cell --}}
                        @if ($hasRowActions)
                            @php $rowActionList = $rowActionsMap[$rowId] ?? []; @endphp
                            <td class="px-3 py-2 text-right align-middle whitespace-nowrap">
                                @if (!empty($rowActionList))
                                    @if ($rowActionsMode === \PredatorStudio\LiveTable\Enums\RowActionsMode::DROPDOWN)
                                        {{-- Dropdown (3-dot) mode --}}
                                        <div class="relative inline-block" x-data="{ openRowActions: false }">
                                            <button
                                                type="button"
                                                @click.stop="openRowActions = !openRowActions"
                                                @click.outside="openRowActions = false"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                                title="Akcje"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16"><circle cx="8" cy="2" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="14" r="1.5"/></svg>
                                            </button>
                                            <div
                                                x-show="openRowActions"
                                                x-transition
                                                class="absolute right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg"
                                                style="min-width: 10rem; z-index: 50;"
                                            >
                                                @foreach ($rowActionList as $rowAction)
                                                    @if ($rowAction->href)
                                                        <a
                                                            href="{{ $rowAction->href }}"
                                                            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                        >
                                                            @if ($rowAction->icon){!! $rowAction->icon !!}@endif
                                                            {{ $rowAction->label }}
                                                        </a>
                                                    @else
                                                        <button
                                                            type="button"
                                                            wire:click="{{ $rowAction->method }}('{{ $rowId }}')"
                                                            @if ($rowAction->confirm) wire:confirm="{{ $rowAction->confirm }}" @endif
                                                            @click.stop="openRowActions = false"
                                                            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 w-full text-left"
                                                        >
                                                            @if ($rowAction->icon){!! $rowAction->icon !!}@endif
                                                            {{ $rowAction->label }}
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        {{-- Icons mode --}}
                                        <div class="flex items-center gap-1 justify-end">
                                            @foreach ($rowActionList as $rowAction)
                                                @if ($rowAction->href)
                                                    <a
                                                        href="{{ $rowAction->href }}"
                                                        class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                                        title="{{ $rowAction->label }}"
                                                    >
                                                        @if ($rowAction->icon){!! $rowAction->icon !!}@else{{ mb_substr($rowAction->label, 0, 1) }}@endif
                                                    </a>
                                                @else
                                                    <button
                                                        type="button"
                                                        wire:click="{{ $rowAction->method }}('{{ $rowId }}')"
                                                        @if ($rowAction->confirm) wire:confirm="{{ $rowAction->confirm }}" @endif
                                                        class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                                        title="{{ $rowAction->label }}"
                                                    >
                                                        @if ($rowAction->icon){!! $rowAction->icon !!}@else{{ mb_substr($rowAction->label, 0, 1) }}@endif
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </td>
                        @endif
                    </tr>

                    {{-- Sub-rows --}}
                    @foreach ($subRowsMap[$rowId] ?? [] as $subRow)
                        <tr x-show="open" style="display:none;" class="bg-gray-50">
                            @if ($expandable)<td></td>@endif
                            @if ($hasCheckboxCol)<td></td>@endif
                            @foreach ($visibleColumns as $col)
                                <td class="px-3 py-2 text-sm text-gray-500 align-middle">{!! $col->renderCell($subRow) !!}</td>
                            @endforeach
                            @if ($hasRowActions)<td></td>@endif
                        </tr>
                    @endforeach
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td
                            colspan="{{ $colspan }}"
                            class="py-12 text-center text-sm text-gray-400"
                        >
                            Brak danych.
                        </td>
                    </tr>
                </tbody>
            @endforelse

            {{-- Infinite scroll: sentinel + loader --}}
            @if ($infiniteMode && !$allLoaded)
                <tbody>
                    <tr>
                        <td colspan="{{ $colspan }}" class="border-0 p-0">
                            <div wire:loading wire:target="loadMore" class="flex justify-center py-3">
                                <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                            <div
                                wire:loading.remove wire:target="loadMore"
                                x-data
                                x-init="
                                    const io = new IntersectionObserver(([e]) => {
                                        if (e.isIntersecting) $wire.loadMore()
                                    }, { rootMargin: '200px' });
                                    io.observe($el);
                                    $destroy(() => io.disconnect());
                                "
                                style="height: 1px;"
                            ></div>
                        </td>
                    </tr>
                </tbody>
            @endif

            {{-- Footer row: sums & counts --}}
            @if (!empty($sumData) || !empty($countData))
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        @if ($hasCheckboxCol)<td class="px-3 py-2"></td>@endif
                        @foreach ($visibleColumns as $col)
                            <td class="px-3 py-2 text-right text-xs font-semibold text-gray-600 whitespace-nowrap">
                                @if (array_key_exists($col->key, $sumData))
                                    <span class="text-gray-400 mr-1 text-xs" title="Suma">Σ</span>{{ $sumData[$col->key] }}
                                @elseif (array_key_exists($col->key, $countData))
                                    <span class="text-gray-400 mr-1 text-xs" title="Liczba">#</span>{{ $countData[$col->key] }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Bottom bar (pagination) --}}
    @if ($total > 0)
        <div class="flex flex-wrap items-center justify-between gap-4">

            {{-- Left: per-page selector --}}
            <div class="flex items-center gap-1 text-sm text-gray-500">
                <span class="mr-1">Wyświetl:</span>
                @foreach ([10, 25, 50, 100, 200] as $option)
                    <button
                        type="button"
                        wire:click="$set('perPage', {{ $option }})"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors {{ $perPage === $option ? 'bg-indigo-600 text-white hover:bg-indigo-500 border border-indigo-600' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                    >{{ $option }}</button>
                @endforeach
                @if ($allowInfiniteScroll)
                    <button
                        type="button"
                        wire:click="$set('perPage', 0)"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors {{ $perPage === 0 ? 'bg-indigo-600 text-white hover:bg-indigo-500 border border-indigo-600' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                    >Wszystkie</button>
                @endif
            </div>

            {{-- Center: page links (normal mode only) --}}
            @if (!$infiniteMode && $lastPage > 1)
                <nav class="flex items-center gap-1">
                    <button
                        type="button"
                        wire:click="setPage({{ max(1, $page - 1) }})"
                        @disabled($page <= 1)
                        class="relative inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >&laquo;</button>

                    @foreach ($pages as $p)
                        @if ($p === '...')
                            <span class="relative inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 bg-white text-gray-400 cursor-default">…</span>
                        @else
                            <button
                                type="button"
                                wire:click="setPage({{ $p }})"
                                class="relative inline-flex items-center px-3 py-1.5 text-sm rounded-md border transition-colors {{ $page === $p ? 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-500' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                            >{{ $p }}</button>
                        @endif
                    @endforeach

                    <button
                        type="button"
                        wire:click="setPage({{ min($lastPage, $page + 1) }})"
                        @disabled($page >= $lastPage)
                        class="relative inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >&raquo;</button>
                </nav>
            @endif

            {{-- Right: showing X–Y of Z --}}
            <p class="text-sm text-gray-700">
                Pokazano <span class="font-medium">{{ $from }}–{{ $to }}</span> z <span class="font-medium">{{ $total }}</span>
            </p>

        </div>
    @endif

    {{-- Filters modal --}}
    @if ($showFiltersModal && count($filterDefs) > 0)
        <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
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

                            @if ($filter->type === 'select')
                                <select
                                    wire:model="activeFilters.{{ $filter->key }}"
                                    class="block w-full rounded-md border-0 py-1 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600"
                                >
                                    <option value="">— Wszystkie —</option>
                                    @foreach ($filter->options as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                            @elseif ($filter->type === 'date')
                                <input
                                    type="date"
                                    wire:model="activeFilters.{{ $filter->key }}"
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                                >

                            @else {{-- text --}}
                                <input
                                    type="text"
                                    wire:model="activeFilters.{{ $filter->key }}"
                                    placeholder="Filtruj po {{ strtolower($filter->label) }}..."
                                    class="block w-full rounded-md border-0 py-1.5 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-3 mt-4">
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="text-sm text-gray-500 underline hover:text-gray-700 focus:outline-none transition-colors"
                    >
                        Wyczyść filtry
                    </button>
                    <button
                        type="button"
                        wire:click="applyActiveFilters"
                        class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    >
                        Zastosuj
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
