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
    class="d-flex flex-column gap-3"
>

    {{-- Top bar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

        {{-- Left: bulk actions + select-page + export --}}
        <div class="d-flex align-items-center gap-2">
            @if ($selectable && count($selected) > 0)
                <span class="small text-muted">
                    {{ count($selected) }} zaznaczonych
                </span>
                @foreach ($bulkActionDefs as $action)
                    <button
                        type="button"
                        wire:click="{{ $action->method }}"
                        title="{{ $action->label }}"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center"
                        style="width: 2rem; height: 2rem; padding: 0;"
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
                    class="btn btn-sm btn-outline-secondary"
                >
                    Zaznacz stronę
                </button>
            @endif

            {{-- Eksport CSV --}}
            @if ($exportCsv)
                <button
                    type="button"
                    wire:click="exportCsv"
                    class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                    title="Eksportuj do CSV"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    CSV
                </button>
            @endif

            {{-- Eksport PDF --}}
            @if ($exportPdf)
                <button
                    type="button"
                    wire:click="exportPdf"
                    class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                    title="Eksportuj do PDF"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    PDF
                </button>
            @endif

            {{-- Mass delete --}}
            @if ($massDeleteEnabled && $selectable)
                <button
                    type="button"
                    wire:click="massDelete"
                    wire:confirm="Czy na pewno chcesz usunąć zaznaczone wiersze?"
                    class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                    title="Usuń zaznaczone"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    Usuń
                </button>
            @endif
        </div>

        {{-- Right side controls --}}
        <div class="d-flex flex-wrap align-items-center gap-2">

            {{-- Column visibility toggle --}}
            @if ($displayColumnList)
                <div class="position-relative" @click.outside="showColumnPanel = false">
                    <button
                        type="button"
                        @click="showColumnPanel = !showColumnPanel"
                        title="Widoczność kolumn"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        Kolumny
                    </button>

                    {{-- Dropdown panel --}}
                    <div
                        x-show="showColumnPanel"
                        x-transition
                        class="position-absolute end-0 mt-1 bg-white border rounded shadow-sm"
                        style="width: 15rem; z-index: 30;"
                    >
                        <p class="border-bottom px-3 py-2 mb-0 small text-muted">
                            Przeciągnij, by zmienić kolejność
                        </p>
                        <ul class="list-unstyled p-1 mb-0">
                            @foreach ($allColumns as $col)
                                <li
                                    class="d-flex align-items-center gap-2 rounded px-2 py-1 small"
                                    :class="dragOverCol === '{{ $col->key }}' ? 'bg-primary bg-opacity-10' : 'text-body'"
                                    style="cursor: grab;"
                                    draggable="true"
                                    @dragstart="startDrag('{{ $col->key }}')"
                                    @dragover.prevent="onDragOver('{{ $col->key }}')"
                                    @drop.prevent="onDrop('{{ $col->key }}')"
                                    @dragend="draggingCol = null; dragOverCol = null"
                                >
                                    {{-- drag handle --}}
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 24 24" class="text-muted flex-shrink-0"><circle cx="9" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>

                                    <span class="flex-grow-1 text-truncate">{{ $col->label }}</span>

                                    <button
                                        type="button"
                                        wire:click="toggleColumn('{{ $col->key }}')"
                                        @click.stop
                                        class="btn btn-link btn-sm p-0 text-muted"
                                        title="{{ in_array($col->key, $hiddenColumns) ? 'Pokaż kolumnę' : 'Ukryj kolumnę' }}"
                                    >
                                        @if (in_array($col->key, $hiddenColumns))
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
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
                    class="btn btn-sm d-inline-flex align-items-center gap-1 {{ $activeFilterCount > 0 ? 'btn-primary' : 'btn-outline-secondary' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Filtry
                    @if ($activeFilterCount > 0)
                        <span class="badge bg-white text-primary rounded-pill" style="font-size: 0.65rem;">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                </button>
            @endif

            {{-- Search --}}
            @if ($displaySearch)
                <div class="position-relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="position-absolute text-muted" style="left: 0.6rem; top: 50%; transform: translateY(-50%); pointer-events: none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Szukaj..."
                        class="form-control form-control-sm"
                        style="padding-left: 2rem; width: 13rem;"
                    >
                </div>
            @endif

            {{-- Header action buttons --}}
            @foreach ($headerActionDefs as $action)
                @if ($action->href)
                    <a
                        href="{{ $action->href }}"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                    >
                        @if ($action->icon){!! $action->icon !!}@endif
                        {{ $action->label }}
                    </a>
                @else
                    <button
                        type="button"
                        wire:click="{{ $action->method }}"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
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
        $activeFilterDefs    = collect($filterDefs)->keyBy('key');
        $displayedFilters    = collect($activeFilters)->filter(fn($v) => $v !== '' && $v !== null);
    @endphp
    @if ($displayedFilters->isNotEmpty())
        <div class="d-flex flex-wrap align-items-center gap-2">
            @foreach ($displayedFilters as $key => $value)
                @php
                    $filterDef    = $activeFilterDefs->get($key);
                    $filterLabel  = $filterDef ? $filterDef->label : $key;
                    $displayValue = ($filterDef && $filterDef->type === 'select' && isset($filterDef->options[$value]))
                        ? $filterDef->options[$value]
                        : $value;
                @endphp
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 0.78rem; font-weight: 500;">
                    <span>{{ $filterLabel }}: {{ $displayValue }}</span>
                    <button
                        type="button"
                        wire:click="removeFilter('{{ $key }}')"
                        class="btn-close btn-close-white ms-1"
                        style="font-size: 0.55rem; filter: invert(1) sepia(1) saturate(5) hue-rotate(175deg);"
                        title="Usuń filtr"
                    ></button>
                </span>
            @endforeach

            @if ($displayedFilters->count() > 1)
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="btn btn-link btn-sm text-muted p-0 text-decoration-underline"
                    style="font-size: 0.78rem;"
                >
                    Wyczyść wszystkie
                </button>
            @endif
        </div>
    @endif

    {{-- Gmail-style select-all banner --}}
    @if ($selectAllQuery)
        <div class="alert alert-info d-flex align-items-center justify-content-between py-2 px-3 mb-0">
            <span class="small">Zaznaczono wszystkie <strong>{{ $total }}</strong> wiersze z wyników.</span>
            <button
                type="button"
                wire:click="clearSelectAllQuery"
                class="btn btn-sm btn-outline-info py-0 px-2"
            >
                Wyczyść zaznaczenie
            </button>
        </div>
    @elseif ($selectable && $allPageSelected && $total > count($currentPageIds))
        <div class="alert alert-info d-flex align-items-center justify-content-between py-2 px-3 mb-0">
            <span class="small">Zaznaczono <strong>{{ count($selected) }}</strong> wierszy z tej strony.</span>
            <button
                type="button"
                wire:click="selectAllFromQuery"
                class="btn btn-sm btn-outline-info py-0 px-2"
            >
                Zaznacz wszystkie {{ $total }} wiersze z wyników →
            </button>
        </div>
    @endif

    {{-- Table --}}
    <div class="table-responsive border rounded" style="max-height: 75vh;">
        <table class="table table-hover table-sm table-bordered mb-0 align-middle">
            <thead class="table-light sticky-top">
                <tr>
                    {{-- Expand column header --}}
                    @if ($expandable)
                        <th style="width: 28px; min-width: 28px;"></th>
                    @endif

                    {{-- Select-all checkbox (only in checkbox mode) --}}
                    @if ($hasCheckboxCol)
                        <th style="width: 2.5rem;">
                            <input
                                type="checkbox"
                                class="form-check-input"
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
                            class="text-muted small fw-semibold user-select-none {{ $col->sortable ? '' : '' }}"
                            :class="dragOverCol === '{{ $col->key }}' ? 'bg-primary bg-opacity-10' : ''"
                            style="cursor: {{ $col->sortable ? 'pointer' : 'grab' }}; white-space: nowrap;"
                            draggable="true"
                            @dragstart="startDrag('{{ $col->key }}')"
                            @dragover.prevent="onDragOver('{{ $col->key }}')"
                            @drop.prevent="onDrop('{{ $col->key }}')"
                            @dragend="draggingCol = null; dragOverCol = null"
                            @if ($col->sortable) wire:click="sort('{{ $col->key }}')" @endif
                        >
                            <span class="d-inline-flex align-items-center gap-1">
                                {{ $col->label }}
                                @if ($col->sortable)
                                    @if ($sortBy === $col->key)
                                        @if ($sortDir === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                        @endif
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="opacity: 0.3;"><polyline points="18 15 12 9 6 15"/></svg>
                                    @endif
                                @endif
                            </span>
                        </th>
                    @endforeach

                    {{-- Row actions column header --}}
                    @if ($hasRowActions)
                        <th style="width: 60px; min-width: 60px;"></th>
                    @endif
                </tr>
            </thead>

            @forelse ($items as $row)
                @php $rowId = (string) data_get($row, $primaryKey); @endphp
                <tbody x-data="{ open: false }">
                    {{-- Main row --}}
                    <tr
                        :class="$wire.selected.includes('{{ $rowId }}') ? 'table-primary' : ''"
                        @if ($isRowSelectMode)
                            wire:click="toggleSelectRow('{{ $rowId }}')"
                            style="cursor: pointer;"
                        @endif
                    >
                        {{-- Expand toggle --}}
                        @if ($expandable)
                            <td style="width: 28px; min-width: 28px;" class="text-center align-middle">
                                @if (count($subRowsMap[$rowId] ?? []) > 0)
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="btn btn-sm p-0 border-0 text-muted"
                                        style="line-height: 1;"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11"
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
                            <td style="width: 2.5rem;">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="$wire.selected.includes('{{ $rowId }}')"
                                    @change="$wire.toggleSelectRow('{{ $rowId }}')"
                                >
                            </td>
                        @endif

                        @foreach ($visibleColumns as $col)
                            <td>{!! $col->renderCell($row, $primaryKey) !!}</td>
                        @endforeach

                        {{-- Row actions cell --}}
                        @if ($hasRowActions)
                            @php $rowActionList = $rowActionsMap[$rowId] ?? []; @endphp
                            <td class="text-end align-middle" style="white-space: nowrap;">
                                @if (!empty($rowActionList))
                                    @if ($rowActionsMode === \PredatorStudio\LiveTable\Enums\RowActionsMode::DROPDOWN)
                                        {{-- Dropdown (3-dot) mode --}}
                                        <div class="position-relative d-inline-block" x-data="{ openRowActions: false }">
                                            <button
                                                type="button"
                                                @click.stop="openRowActions = !openRowActions"
                                                @click.outside="openRowActions = false"
                                                class="btn btn-sm btn-outline-secondary border-0 p-0 d-inline-flex align-items-center justify-content-center"
                                                style="width: 1.75rem; height: 1.75rem;"
                                                title="Akcje"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><circle cx="8" cy="2" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="14" r="1.5"/></svg>
                                            </button>
                                            <div
                                                x-show="openRowActions"
                                                x-transition
                                                class="position-absolute end-0 mt-1 bg-white border rounded shadow-sm"
                                                style="min-width: 10rem; z-index: 50;"
                                            >
                                                @foreach ($rowActionList as $rowAction)
                                                    @if ($rowAction->href)
                                                        <a
                                                            href="{{ $rowAction->href }}"
                                                            class="dropdown-item d-flex align-items-center gap-2 small px-3 py-2"
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
                                                            class="dropdown-item d-flex align-items-center gap-2 small px-3 py-2 w-100 text-start border-0 bg-transparent"
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
                                        <div class="d-flex gap-1 justify-content-end">
                                            @foreach ($rowActionList as $rowAction)
                                                @if ($rowAction->href)
                                                    <a
                                                        href="{{ $rowAction->href }}"
                                                        class="btn btn-sm btn-outline-secondary border-0 p-0 d-inline-flex align-items-center justify-content-center"
                                                        style="width: 1.75rem; height: 1.75rem;"
                                                        title="{{ $rowAction->label }}"
                                                    >
                                                        @if ($rowAction->icon){!! $rowAction->icon !!}@else{{ mb_substr($rowAction->label, 0, 1) }}@endif
                                                    </a>
                                                @else
                                                    <button
                                                        type="button"
                                                        wire:click="{{ $rowAction->method }}('{{ $rowId }}')"
                                                        @if ($rowAction->confirm) wire:confirm="{{ $rowAction->confirm }}" @endif
                                                        class="btn btn-sm btn-outline-secondary border-0 p-0 d-inline-flex align-items-center justify-content-center"
                                                        style="width: 1.75rem; height: 1.75rem;"
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
                        <tr x-show="open" style="display:none;" class="table-secondary">
                            @if ($expandable)<td></td>@endif
                            @if ($hasCheckboxCol)<td></td>@endif
                            @foreach ($visibleColumns as $col)
                                <td class="text-muted">{!! $col->renderCell($subRow) !!}</td>
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
                            class="text-center text-muted py-5 small"
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
                            {{-- Loader shown while Livewire processes loadMore() --}}
                            <div wire:loading wire:target="loadMore" class="text-center py-2">
                                <div class="spinner-border spinner-border-sm text-secondary" role="status">
                                    <span class="visually-hidden">Ładowanie...</span>
                                </div>
                            </div>
                            {{-- Sentinel: invisible element that triggers loadMore when scrolled into view --}}
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
                <tfoot class="table-light border-top">
                    <tr>
                        @if ($hasCheckboxCol)<td></td>@endif
                        @foreach ($visibleColumns as $col)
                            <td class="small fw-semibold text-end" style="white-space: nowrap;">
                                @if (array_key_exists($col->key, $sumData))
                                    <span class="text-muted me-1" style="font-size: 0.7rem;" title="Suma">Σ</span>{{ $sumData[$col->key] }}
                                @elseif (array_key_exists($col->key, $countData))
                                    <span class="text-muted me-1" style="font-size: 0.7rem;" title="Liczba">#</span>{{ $countData[$col->key] }}
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
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

            {{-- Left: per-page selector --}}
            <div class="d-flex align-items-center gap-1 small text-muted">
                <span class="me-1">Wyświetl:</span>
                @foreach ([10, 25, 50, 100, 200] as $option)
                    <button
                        type="button"
                        wire:click="$set('perPage', {{ $option }})"
                        class="btn btn-sm {{ $perPage === $option ? 'btn-primary' : 'btn-outline-secondary' }} px-2 py-0"
                        style="font-size: 0.8rem;"
                    >{{ $option }}</button>
                @endforeach
                @if ($allowInfiniteScroll)
                    <button
                        type="button"
                        wire:click="$set('perPage', 0)"
                        class="btn btn-sm {{ $perPage === 0 ? 'btn-primary' : 'btn-outline-secondary' }} px-2 py-0"
                        style="font-size: 0.8rem;"
                    >Wszystkie</button>
                @endif
            </div>

            {{-- Center: page links (normal mode only) --}}
            @if (!$infiniteMode && $lastPage > 1)
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                            <button type="button" class="page-link" wire:click="setPage({{ max(1, $page - 1) }})">
                                &laquo;
                            </button>
                        </li>

                        @foreach ($pages as $p)
                            @if ($p === '...')
                                <li class="page-item disabled">
                                    <span class="page-link">…</span>
                                </li>
                            @else
                                <li class="page-item {{ $page === $p ? 'active' : '' }}">
                                    <button type="button" class="page-link" wire:click="setPage({{ $p }})">
                                        {{ $p }}
                                    </button>
                                </li>
                            @endif
                        @endforeach

                        <li class="page-item {{ $page >= $lastPage ? 'disabled' : '' }}">
                            <button type="button" class="page-link" wire:click="setPage({{ min($lastPage, $page + 1) }})">
                                &raquo;
                            </button>
                        </li>
                    </ul>
                </nav>
            @endif

            {{-- Right: showing X–Y of Z --}}
            <div class="small text-muted">
                Pokazano
                <strong>{{ $from }}–{{ $to }}</strong>
                z
                <strong>{{ $total }}</strong>
            </div>

        </div>
    @endif

    {{-- Filters modal --}}
    @if ($showFiltersModal && count($filterDefs) > 0)
        <div
            class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
            style="z-index: 1050; background: rgba(0,0,0,0.5);"
            @keydown.escape.window="$wire.set('showFiltersModal', false)"
        >
            <div class="bg-white border rounded-3 shadow-lg p-4" style="width: 100%; max-width: 28rem;">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="fw-bold mb-0">Filtry</h5>
                    <button
                        type="button"
                        wire:click="$set('showFiltersModal', false)"
                        class="btn-close"
                    ></button>
                </div>

                <div class="d-flex flex-column gap-3">
                    @foreach ($filterDefs as $filter)
                        <div>
                            <label class="form-label small fw-medium mb-1">
                                {{ $filter->label }}
                            </label>

                            @if ($filter->type === 'select')
                                <select
                                    wire:model="activeFilters.{{ $filter->key }}"
                                    class="form-select form-select-sm"
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
                                    class="form-control form-control-sm"
                                >

                            @else {{-- text --}}
                                <input
                                    type="text"
                                    wire:model="activeFilters.{{ $filter->key }}"
                                    placeholder="Filtruj po {{ strtolower($filter->label) }}..."
                                    class="form-control form-control-sm"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="d-flex align-items-center justify-content-between gap-3 mt-4">
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="btn btn-link btn-sm text-muted text-decoration-underline p-0"
                    >
                        Wyczyść filtry
                    </button>
                    <button
                        type="button"
                        wire:click="applyActiveFilters"
                        class="btn btn-primary btn-sm px-4"
                    >
                        Zastosuj
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
