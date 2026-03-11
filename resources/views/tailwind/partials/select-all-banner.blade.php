@if ($selectAllQuery)
    <div class="flex items-center justify-between gap-3 rounded-md border border-blue-300 px-4 py-2 text-sm text-blue-700" style="background: rgba(59,130,246,0.07);">
        <span>Zaznaczono wszystkie <strong>{{ $total }}</strong> wiersze z wyników.</span>
        <button
            type="button"
            wire:click="clearSelectAllQuery"
            class="text-xs font-medium text-blue-600 hover:text-blue-800 underline focus:outline-none transition-colors"
        >
            Wyczyść zaznaczenie
        </button>
    </div>
@elseif ($selectable && count($selected) > 0)
    <div class="flex items-center justify-between gap-3 rounded-md border border-blue-300 px-4 py-2 text-sm text-blue-700" style="background: rgba(59,130,246,0.07);">
        <span><strong>{{ count($selected) }}</strong> zaznaczonych</span>
        <div class="flex items-center gap-3">
            <button
                type="button"
                wire:click="selectRows({{ json_encode($currentPageIds) }})"
                class="text-xs font-medium text-blue-600 hover:text-blue-800 underline focus:outline-none transition-colors"
            >
                Zaznacz stronę
            </button>
            @if ($total > count($selected))
                <button
                    type="button"
                    wire:click="selectAllFromQuery"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800 underline focus:outline-none transition-colors"
                >
                    Zaznacz wszystkie ({{ $total }}) →
                </button>
            @endif
        </div>
    </div>
@endif