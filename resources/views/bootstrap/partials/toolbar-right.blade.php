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

    {{-- Filters button --}}
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

    {{-- Default creating button --}}
    @if ($canCreate)
        <button
            type="button"
            wire:click="openCreatingModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Dodaj rekord
        </button>
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