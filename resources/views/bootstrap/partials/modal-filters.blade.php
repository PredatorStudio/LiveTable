@if ($showFiltersModal && count($filterDefs) > 0)
    <div
        class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
        style="z-index: 1050; background: rgba(0,0,0,0.5);"
        @keydown.escape.window="$wire.set('showFiltersModal', false)"
    >
        <div class="bg-white border rounded-3 shadow-lg p-4" style="width: 100%; max-width: 28rem;">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold mb-0">Filtry</h5>
                <button type="button" wire:click="$set('showFiltersModal', false)" class="btn-close"></button>
            </div>

            <div class="d-flex flex-column gap-3">
                @foreach ($filterDefs as $filter)
                    <div>
                        <label class="form-label small fw-medium mb-1">
                            {{ $filter->label }}
                        </label>

                        @if ($filter->type->value === 'select')
                            <select
                                wire:model="activeFilters.{{ $filter->key }}"
                                class="form-select form-select-sm"
                            >
                                <option value="">— Wszystkie —</option>
                                @foreach ($filter->options as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @elseif ($filter->type->value === 'date')
                            <input
                                type="date"
                                wire:model="activeFilters.{{ $filter->key }}"
                                class="form-control form-control-sm"
                            >
                        @else
                            <input
                                type="text"
                                wire:model="activeFilters.{{ $filter->key }}"
                                placeholder="Filtruj po {{ strtolower($filter->label) }}..."
                                class="form-control form-control-sm"
                            >
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="d-flex align-items-center justify-content-between gap-3 mt-4">
                <button type="button" wire:click="clearFilters" class="btn btn-link btn-sm text-muted text-decoration-underline p-0">
                    Wyczyść filtry
                </button>
                <button type="button" wire:click="applyActiveFilters" class="btn btn-primary btn-sm px-4">
                    Zastosuj
                </button>
            </div>
        </div>
    </div>
@endif
