@php
    $activeFilterDefs = collect($filterDefs)->keyBy('key');
    $displayedFilters = collect($activeFilters)->filter(fn($v) => $v !== '' && $v !== null);
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