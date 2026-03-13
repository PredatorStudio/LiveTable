@if ($showCreatingModal && $canCreate)
    <div
        class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
        style="z-index: 1050; background: rgba(0,0,0,0.5);"
        @keydown.escape.window="$wire.set('showCreatingModal', false)"
    >
        <div class="bg-white border rounded-3 shadow-lg p-4" style="width: 100%; max-width: 28rem; max-height: 90vh; overflow-y: auto;">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold mb-0">Dodaj rekord</h5>
                <button type="button" wire:click="$set('showCreatingModal', false)" class="btn-close"></button>
            </div>

            <div class="d-flex flex-column gap-0">
                @include('live-table::partials.form-fields', ['fields' => $creatingFields, 'dataKey' => 'creatingData', 'placeholder' => ''])
            </div>

            <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                <button type="button" wire:click="$set('showCreatingModal', false)" class="btn btn-outline-secondary btn-sm">
                    Anuluj
                </button>
                <button type="button" wire:click="createRecord" class="btn btn-primary btn-sm px-4">
                    Zapisz
                </button>
            </div>
        </div>
    </div>
@endif