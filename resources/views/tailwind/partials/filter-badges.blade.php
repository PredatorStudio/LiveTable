@php
    $activeFilterDefs = collect($filterDefs)->keyBy('key');

    $displayedFilters = collect($activeFilters)->filter(function ($v) {
        if (is_array($v)) {
            return ($v['from'] ?? '') !== '' || ($v['to'] ?? '') !== '';
        }
        return $v !== '' && $v !== null;
    });
@endphp
@if ($displayedFilters->isNotEmpty())
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($displayedFilters as $key => $value)
            @php
                $filterDef   = $activeFilterDefs->get($key);
                $filterLabel = $filterDef ? $filterDef->label : $key;

                if (is_array($value)) {
                    $from = $value['from'] ?? '';
                    $to   = $value['to'] ?? '';
                    if ($from !== '' && $to !== '') {
                        $displayValue = $from . ' – ' . $to;
                    } elseif ($from !== '') {
                        $displayValue = 'od ' . $from;
                    } else {
                        $displayValue = 'do ' . $to;
                    }
                } elseif ($filterDef && $filterDef->type->value === 'select' && isset($filterDef->options[$value])) {
                    $displayValue = $filterDef->options[$value];
                } elseif ($filterDef && $filterDef->type->value === 'boolean') {
                    $displayValue = $value === '1' ? 'Tak' : 'Nie';
                } else {
                    $displayValue = $value;
                }
            @endphp
            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 border border-indigo-200 px-2.5 py-1 text-xs font-medium text-indigo-700">
                <span>{{ $filterLabel }}: {{ $displayValue }}</span>
                <button
                    type="button"
                    wire:click="removeFilter('{{ $key }}')"
                    class="flex-shrink-0 rounded-full p-0.5 text-indigo-400 hover:text-indigo-500 hover:bg-indigo-100 focus:outline-none transition-colors"
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
