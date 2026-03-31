<div class="overflow-auto border border-gray-200 rounded-lg" style="max-height: 75vh;">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 sticky top-0 z-10">
            <tr>
                @if ($expandable)
                    <th style="width: 28px; min-width: 28px;"></th>
                @endif

                @if ($hasCheckboxCol)
                    <th class="px-3 py-2" style="width: 2.5rem;">
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-500 focus:ring-indigo-500 cursor-pointer"
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

                @if ($hasRowActions)
                    <th class="px-3 py-2" style="width: 60px; min-width: 60px;"></th>
                @endif
            </tr>
        </thead>

        @forelse ($items as $row)
            @php $rowId = (string) data_get($row, $primaryKey); @endphp
            <tbody class="bg-white divide-y divide-gray-100" x-data="{ open: false }">
                <tr
                    :class="Array.from($wire.selected).includes('{{ $rowId }}') ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                    @if ($isRowSelectMode)
                        wire:click="toggleSelectRow('{{ $rowId }}')"
                        style="cursor: pointer;"
                    @endif
                >
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
                                class="h-4 w-4 rounded border-gray-300 text-indigo-500 focus:ring-indigo-500 cursor-pointer"
                                :checked="Array.from($wire.selected).includes('{{ $rowId }}')"
                                @change="$wire.toggleSelectRow('{{ $rowId }}')"
                            >
                        </td>
                    @endif

                    @foreach ($visibleColumns as $col)
                        <td class="px-3 py-2 text-sm text-gray-700 align-middle">{!! $col->renderCell($row, $primaryKey) !!}</td>
                    @endforeach

                    @if ($hasRowActions)
                        @php $rowActionList = $rowActionsMap[$rowId] ?? []; @endphp
                        <td class="px-3 py-2 text-right align-middle whitespace-nowrap">
                            @if (!empty($rowActionList))
                                @if ($rowActionsMode === \PredatorStudio\LiveTable\Enums\RowActionsMode::DROPDOWN)
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
                                                        @if ($rowAction->confirm)
                                                            @click.prevent="$dispatch('live-table-ask-confirm', { message: '{{ $rowAction->confirm }}', action: () => $wire.{{ $rowAction->method }}('{{ $rowId }}') }); openRowActions = false"
                                                        @else
                                                            wire:click="{{ $rowAction->method }}('{{ $rowId }}')"
                                                            @click.stop="openRowActions = false"
                                                        @endif
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
                                                    @if ($rowAction->confirm)
                                                        @click.prevent="$dispatch('live-table-ask-confirm', { message: '{{ $rowAction->confirm }}', action: () => $wire.{{ $rowAction->method }}('{{ $rowId }}') })"
                                                    @else
                                                        wire:click="{{ $rowAction->method }}('{{ $rowId }}')"
                                                    @endif
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

                @foreach ($subRowsMap[$rowId] ?? [] as $subRow)
                    @php $subRowId = (string) data_get($subRow, $subRowPrimaryKey); @endphp
                    <tr x-show="open" style="display:none;" class="bg-gray-50">
                        @if ($expandable)<td></td>@endif

                        @if ($hasCheckboxCol)
                            <td class="px-3 py-2" style="width: 2.5rem;">
                                @if ($subRowsSelectable)
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-500 focus:ring-indigo-500 cursor-pointer"
                                        :checked="Array.from($wire.selectedSubRows).includes('{{ $subRowId }}')"
                                        @change="$wire.toggleSelectSubRow('{{ $subRowId }}')"
                                    >
                                @endif
                            </td>
                        @endif

                        @foreach ($visibleColumns as $col)
                            <td class="px-3 py-2 text-sm text-gray-500 align-middle">{!! $col->renderCell($subRow) !!}</td>
                        @endforeach

                        @if ($hasRowActions)
                            @php $subRowActionList = $subRowActionsMap[$rowId][$subRowId] ?? []; @endphp
                            <td class="px-3 py-2 text-right align-middle whitespace-nowrap">
                                @if (!empty($subRowActionList))
                                    <div class="relative inline-block" x-data="{ openSubRowActions: false }">
                                        <button
                                            type="button"
                                            @click.stop="openSubRowActions = !openSubRowActions"
                                            @click.outside="openSubRowActions = false"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                            title="Akcje"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16"><circle cx="8" cy="2" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="14" r="1.5"/></svg>
                                        </button>
                                        <div
                                            x-show="openSubRowActions"
                                            x-transition
                                            class="absolute right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg"
                                            style="min-width: 10rem; z-index: 50;"
                                        >
                                            @foreach ($subRowActionList as $subRowAction)
                                                @if ($subRowAction->href)
                                                    <a
                                                        href="{{ $subRowAction->href }}"
                                                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
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
                                                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 w-full text-left"
                                                    >
                                                        @if ($subRowAction->icon){!! $subRowAction->icon !!}@endif
                                                        {{ $subRowAction->label }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
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
                    <td colspan="{{ $colspan }}" class="py-12 text-center text-sm text-gray-400">
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

        {{-- Footer: sums & counts --}}
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
