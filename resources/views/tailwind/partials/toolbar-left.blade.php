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

    {{-- Mass edit --}}
    @if ($massEditEnabled && ($selectAllQuery || count($selected) > 0))
        <button
            type="button"
            wire:click="openMassEditModal"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            title="Edytuj zaznaczone"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edytuj zaznaczone
        </button>
    @endif

    {{-- Mass delete --}}
    @if ($massDeleteEnabled && $selectable)
        <button
            type="button"
            @click.prevent="$dispatch('live-table-ask-confirm', { message: 'Czy na pewno chcesz usunąć zaznaczone wiersze?', action: () => $wire.massDelete() })"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md border border-red-300 bg-white text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
            title="Usuń zaznaczone"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            Usuń
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
</div>
