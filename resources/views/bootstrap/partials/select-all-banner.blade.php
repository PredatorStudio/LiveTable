@if ($selectAllQuery)
    <div class="d-flex align-items-center justify-content-between gap-2 rounded py-2 px-3 border border-primary" style="background: rgba(99,102,241,0.07);">
        <span class="small text-primary">Zaznaczono wszystkie <strong>{{ $total }}</strong> wiersze z wyników.</span>
        <button
            type="button"
            wire:click="clearSelectAllQuery"
            class="btn btn-sm btn-outline-primary py-0 px-2"
        >
            Wyczyść zaznaczenie
        </button>
    </div>
@elseif ($selectable && count($selected) > 0)
    <div class="d-flex align-items-center justify-content-between gap-2 rounded py-2 px-3 border border-primary" style="background: rgba(99,102,241,0.07);">
        <span class="small text-primary"><strong>{{ count($selected) }}</strong> zaznaczonych</span>
        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                wire:click="selectRows({{ json_encode($currentPageIds) }})"
                class="btn btn-sm btn-outline-primary py-0 px-2"
            >
                Zaznacz stronę
            </button>
            @if ($total > count($selected))
                <button
                    type="button"
                    wire:click="selectAllFromQuery"
                    class="btn btn-sm btn-outline-primary py-0 px-2"
                >
                    Zaznacz wszystkie ({{ $total }})
                </button>
            @endif
        </div>
    </div>
@endif