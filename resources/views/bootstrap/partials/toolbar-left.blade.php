<div class="d-flex align-items-center gap-2">
    @if ($selectable && count($selected) > 0)
        <span class="small text-muted">
            {{ count($selected) }} zaznaczonych
        </span>
        @foreach ($bulkActionDefs as $action)
            <button
                type="button"
                @if ($massActionRequiresConfirmation)
                    @click.prevent="$dispatch('live-table-ask-confirm', { message: 'Czy na pewno chcesz wykonać akcję: {{ $action->label }}?', action: () => $wire.{{ $action->method }}() })"
                @else
                    wire:click="{{ $action->method }}"
                @endif
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

    {{-- Mass edit --}}
    @if ($massEditEnabled && ($selectAllQuery || count($selected) > 0))
        <button
            type="button"
            wire:click="openMassEditModal"
            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
            title="Edytuj zaznaczone"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edytuj zaznaczone
        </button>
    @endif

    {{-- Mass delete --}}
    @if ($massDeleteEnabled && $selectable)
        <button
            type="button"
            @if ($massActionRequiresConfirmation)
                @click.prevent="$dispatch('live-table-ask-confirm', { message: 'Czy na pewno chcesz usunąć zaznaczone wiersze?', action: () => $wire.massDelete() })"
            @else
                wire:click="massDelete"
            @endif
            class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
            title="Usuń zaznaczone"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            Usuń
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
</div>