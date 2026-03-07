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

        {{-- Left: bulk actions (visible when rows are selected) --}}
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

    {{-- Table --}}
    <div class="table-responsive border rounded" style="max-height: 75vh;">
        <table class="table table-hover table-sm table-bordered mb-0 align-middle">
            <thead class="table-light sticky-top">
                <tr>
                    {{-- Select-all checkbox --}}
                    @if ($selectable)
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
                </tr>
            </thead>

            <tbody>
                @forelse ($items as $row)
                    @php $rowId = (string) data_get($row, $primaryKey); @endphp
                    <tr :class="$wire.selected.includes('{{ $rowId }}') ? 'table-primary' : ''">
                        @if ($selectable)
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
                            <td>{!! $col->renderCell($row) !!}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="{{ count($visibleColumns) + ($selectable ? 1 : 0) }}"
                            class="text-center text-muted py-5 small"
                        >
                            Brak danych.
                        </td>
                    </tr>
                @endforelse
            </tbody>
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
            </div>

            {{-- Center: page links --}}
            @if ($lastPage > 1)
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
