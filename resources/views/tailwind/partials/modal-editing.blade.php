@if ($showEditingModal && $canEdit && count($editingFields) > 0)
    <div
        class="fixed inset-0 bg-gray-500/75 flex items-center justify-center z-50"
        @keydown.escape.window="$wire.set('showEditingModal', false)"
    >
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h5 class="font-semibold text-gray-900 text-base">Edytuj rekord</h5>
                <button
                    type="button"
                    wire:click="$set('showEditingModal', false)"
                    class="text-gray-400 hover:text-gray-600 focus:outline-none transition-colors"
                    title="Zamknij"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            @include('live-table::partials.form-fields', ['fields' => $editingFields, 'dataKey' => 'editingData', 'placeholder' => ''])

            <div class="flex items-center justify-end gap-2 mt-2">
                <button type="button" wire:click="$set('showEditingModal', false)"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    Anuluj
                </button>
                <button type="button" wire:click="updateRecord"
                    class="inline-flex items-center px-4 py-1.5 text-xs font-medium rounded-md bg-indigo-500 text-white hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    Zapisz
                </button>
            </div>
        </div>
    </div>
@endif
