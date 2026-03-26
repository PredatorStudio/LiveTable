@if ($selectAllQuery)
    <div class="flex items-center justify-between gap-3 rounded-md border border-indigo-300 px-4 py-2 text-sm text-indigo-700" style="background: rgba(99,102,241,0.07);">
        <span>Zaznaczono wszystkie <strong>{{ $total }}</strong> wiersze z wyników.</span>
        <button
            type="button"
            wire:click="clearSelectAllQuery"
            class="text-xs font-medium text-indigo-500 hover:text-indigo-700 underline focus:outline-none transition-colors"
        >
            Wyczyść zaznaczenie
        </button>
    </div>
@elseif ($selectable && count($selected) > 0)
    @if (count($selected) > 500)
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-700 mb-1">
            Zaznaczono {{ count($selected) }} rekordów – może to spowolnić działanie.
        </div>
    @endif
    <div class="flex items-center justify-between gap-3 rounded-md border border-indigo-300 px-4 py-2 text-sm text-indigo-700" style="background: rgba(99,102,241,0.07);">
        <span><strong>{{ count($selected) }}</strong> zaznaczonych</span>
        <div class="flex items-center gap-3">
            <button
                type="button"
                wire:click="selectRows({{ json_encode($currentPageIds) }})"
                class="text-xs font-medium text-indigo-500 hover:text-indigo-700 underline focus:outline-none transition-colors"
            >
                Zaznacz stronę
            </button>
            @if ($total > count($selected))
                <button
                    type="button"
                    wire:click="selectAllFromQuery"
                    class="text-xs font-medium text-indigo-500 hover:text-indigo-700 underline focus:outline-none transition-colors"
                >
                    Zaznacz wszystkie ({{ $total }}) →
                </button>
            @endif
        </div>
    </div>
@endif