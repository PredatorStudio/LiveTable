<div class="table-responsive border rounded" style="max-height: 75vh;">
    <table class="table table-hover table-sm table-bordered mb-0 align-middle">
        <thead class="table-light sticky-top">
            <tr>
                @if ($expandable)
                    <th style="width: 28px; min-width: 28px; background: #6366f1;"></th>
                @endif

                @if ($hasCheckboxCol)
                    <th style="width: 2.5rem; background: #6366f1;">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            :checked="{{ json_encode($currentPageIds) }}.length > 0
                                && {{ json_encode($currentPageIds) }}.every(id => Array.from($wire.selected).includes(id))"
                            @change="$event.target.checked
                                ? $wire.selectRows({{ json_encode($currentPageIds) }})
                                : $wire.deselectRows({{ json_encode($currentPageIds) }})"
                        >
                    </th>
                @endif

                @foreach ($visibleColumns as $col)
                    <th
                        class="text-muted small fw-semibold user-select-none"
                        :class="dragOverCol === '{{ $col->key }}' ? 'bg-primary bg-opacity-10' : ''"
                        style="cursor: {{ $col->sortable ? 'pointer' : 'grab' }}; white-space: nowrap; background: #6366f1;{{ $col->width ? ' width: '.$col->width.'; min-width: '.$col->width.';' : '' }}"
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

                @if ($hasRowActions)
                    <th style="width: 60px; min-width: 60px; background: #6366f1;"></th>
                @endif
            </tr>
        </thead>

        @forelse ($items as $row)
            @php $rowId = (string) data_get($row, $primaryKey); @endphp
            <tbody x-data="{ open: false }">
                <tr
                    :class="Array.from($wire.selected).includes('{{ $rowId }}') ? 'table-primary' : ''"
                    @if ($isRowSelectMode)
                        wire:click="toggleSelectRow('{{ $rowId }}')"
                        style="cursor: pointer;"
                    @endif
                >
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
                                :checked="Array.from($wire.selected).includes('{{ $rowId }}')"
                                @change="$wire.toggleSelectRow('{{ $rowId }}')"
                            >
                        </td>
                    @endif

                    @foreach ($visibleColumns as $col)
                        <td>{!! $col->renderCell($row, $primaryKey) !!}</td>
                    @endforeach

                    @if ($hasRowActions)
                        @php $rowActionList = $rowActionsMap[$rowId] ?? []; @endphp
                        <td class="text-end align-middle" style="white-space: nowrap;">
                            @if (!empty($rowActionList))
                                @if ($rowActionsMode === \PredatorStudio\LiveTable\Enums\RowActionsMode::DROPDOWN)
                                    <div class="d-inline-block" x-data="{
                                        openRowActions: false,
                                        ddStyle: '',
                                        openDD(btn) {
                                            const r = btn.getBoundingClientRect();
                                            this.ddStyle = 'top:' + (r.bottom + 4) + 'px;right:' + (window.innerWidth - r.right) + 'px;';
                                            window.dispatchEvent(new CustomEvent('lt-close-dropdowns'));
                                            this.openRowActions = true;
                                        }
                                    }"
                                    @lt-close-dropdowns.window="openRowActions = false">
                                        <button
                                            type="button"
                                            @click.stop="openRowActions ? (openRowActions = false) : openDD($el)"
                                            class="btn btn-sm btn-outline-secondary border-0 p-0 d-inline-flex align-items-center justify-content-center"
                                            style="width: 1.75rem; height: 1.75rem;"
                                            title="Akcje"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><circle cx="8" cy="2" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="14" r="1.5"/></svg>
                                        </button>
                                        <template x-teleport="body">
                                            <div
                                                x-show="openRowActions"
                                                @click.outside="openRowActions = false"
                                                x-transition
                                                class="position-fixed bg-white border rounded shadow-sm"
                                                :style="ddStyle"
                                                style="min-width: 10rem; z-index: 9999;"
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
                                                            @if ($rowAction->confirm)
                                                                @click.prevent="$dispatch('live-table-ask-confirm', { message: '{{ $rowAction->confirm }}', action: () => $wire.{{ $rowAction->method }}('{{ $rowId }}') }); openRowActions = false"
                                                            @else
                                                                wire:click="{{ $rowAction->method }}('{{ $rowId }}')"
                                                                @click.stop="openRowActions = false"
                                                            @endif
                                                            class="dropdown-item d-flex align-items-center gap-2 small px-3 py-2 w-100 text-start border-0 bg-transparent"
                                                        >
                                                            @if ($rowAction->icon){!! $rowAction->icon !!}@endif
                                                            {{ $rowAction->label }}
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </template>
                                    </div>
                                @else
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
                                                    @if ($rowAction->confirm)
                                                        @click.prevent="$dispatch('live-table-ask-confirm', { message: '{{ $rowAction->confirm }}', action: () => $wire.{{ $rowAction->method }}('{{ $rowId }}') })"
                                                    @else
                                                        wire:click="{{ $rowAction->method }}('{{ $rowId }}')"
                                                    @endif
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

                @foreach ($subRowsMap[$rowId] ?? [] as $subRow)
                    @php $subRowId = (string) data_get($subRow, $subRowPrimaryKey); @endphp
                    <tr x-show="open" style="display:none;" class="table-secondary">
                        @if ($expandable)<td></td>@endif

                        @if ($hasCheckboxCol)
                            <td style="width: 2.5rem;">
                                @if ($subRowsSelectable)
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        :checked="Array.from($wire.selectedSubRows).includes('{{ $subRowId }}')"
                                        @change="$wire.toggleSelectSubRow('{{ $subRowId }}')"
                                    >
                                @endif
                            </td>
                        @endif

                        @foreach ($visibleColumns as $col)
                            <td class="text-muted">{!! $col->renderCell($subRow) !!}</td>
                        @endforeach

                        @if ($hasRowActions)
                            @php $subRowActionList = $subRowActionsMap[$rowId][$subRowId] ?? []; @endphp
                            <td class="text-end align-middle" style="white-space: nowrap;">
                                @if (!empty($subRowActionList))
                                    <div class="d-inline-block" x-data="{
                                        openSubRowActions: false,
                                        ddStyle: '',
                                        openDD(btn) {
                                            const r = btn.getBoundingClientRect();
                                            this.ddStyle = 'top:' + (r.bottom + 4) + 'px;right:' + (window.innerWidth - r.right) + 'px;';
                                            window.dispatchEvent(new CustomEvent('lt-close-dropdowns'));
                                            this.openSubRowActions = true;
                                        }
                                    }"
                                    @lt-close-dropdowns.window="openSubRowActions = false">
                                        <button
                                            type="button"
                                            @click.stop="openSubRowActions ? (openSubRowActions = false) : openDD($el)"
                                            class="btn btn-sm btn-outline-secondary border-0 p-0 d-inline-flex align-items-center justify-content-center"
                                            style="width: 1.75rem; height: 1.75rem;"
                                            title="Akcje"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><circle cx="8" cy="2" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="14" r="1.5"/></svg>
                                        </button>
                                        <template x-teleport="body">
                                            <div
                                                x-show="openSubRowActions"
                                                @click.outside="openSubRowActions = false"
                                                x-transition
                                                class="position-fixed bg-white border rounded shadow-sm"
                                                :style="ddStyle"
                                                style="min-width: 10rem; z-index: 9999;"
                                            >
                                                @foreach ($subRowActionList as $subRowAction)
                                                    @if ($subRowAction->href)
                                                        <a
                                                            href="{{ $subRowAction->href }}"
                                                            class="dropdown-item d-flex align-items-center gap-2 small px-3 py-2"
                                                        >
                                                            @if ($subRowAction->icon){!! $subRowAction->icon !!}@endif
                                                            {{ $subRowAction->label }}
                                                        </a>
                                                    @else
                                                        <button
                                                            type="button"
                                                            @if ($subRowAction->confirm)
                                                                @click.prevent="$dispatch('live-table-ask-confirm', { message: '{{ $subRowAction->confirm }}', action: () => $wire.{{ $subRowAction->method }}('{{ $subRowId }}') }); openSubRowActions = false"
                                                            @else
                                                                wire:click="{{ $subRowAction->method }}('{{ $subRowId }}')"
                                                                @click.stop="openSubRowActions = false"
                                                            @endif
                                                            class="dropdown-item d-flex align-items-center gap-2 small px-3 py-2 w-100 text-start border-0 bg-transparent"
                                                        >
                                                            @if ($subRowAction->icon){!! $subRowAction->icon !!}@endif
                                                            {{ $subRowAction->label }}
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </template>
                                    </div>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        @empty
            <tbody>
                <tr>
                    <td colspan="{{ $colspan }}" class="text-center text-muted py-5 small">
                        Brak danych.
                    </td>
                </tr>
            </tbody>
        @endforelse

        {{-- Infinite scroll sentinel --}}
        @if ($infiniteMode && !$allLoaded)
            <tbody>
                <tr>
                    <td colspan="{{ $colspan }}" class="border-0 p-0">
                        <div wire:loading wire:target="loadMore" class="text-center py-2">
                            <div class="spinner-border spinner-border-sm text-secondary" role="status">
                                <span class="visually-hidden">Ładowanie...</span>
                            </div>
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

        {{-- Footer: sums & counts --}}
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