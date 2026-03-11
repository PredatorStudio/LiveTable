<div
    x-data="{
        showColumnPanel: false,
        draggingCol: null,
        dragOverCol: null,
        startDrag(key) {
            this.draggingCol = key;
        },
        onDragOver(key) {
            if (this.draggingCol !== key) this.dragOverCol = key;
        },
        onDrop(key) {
            if (this.draggingCol && this.draggingCol !== key) {
                const order = JSON.parse(JSON.stringify($wire.columnOrder));
                const from  = order.indexOf(this.draggingCol);
                const to    = order.indexOf(key);
                if (from !== -1 && to !== -1) {
                    order.splice(to, 0, order.splice(from, 1)[0]);
                    $wire.reorderColumns(order);
                }
            }
            this.draggingCol = null;
            this.dragOverCol = null;
        }
    }"
    class="d-flex flex-column gap-3"
>

    {{-- Top bar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        @include('live-table::partials.toolbar-left')
        @include('live-table::partials.toolbar-right')
    </div>

    {{-- Active filter badges --}}
    @include('live-table::partials.filter-badges')

    {{-- Select-all banner --}}
    @include('live-table::partials.select-all-banner')

    {{-- Table --}}
    @include('live-table::partials.table')

    {{-- Pagination --}}
    @include('live-table::partials.pagination')

    {{-- Modals --}}
    @include('live-table::partials.modal-creating')
    @include('live-table::partials.modal-editing')
    @include('live-table::partials.modal-mass-edit')
    @include('live-table::partials.modal-filters')

    {{-- Toast notifications --}}
    @include('live-table::partials.toasts')

</div>
