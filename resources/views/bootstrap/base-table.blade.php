<style>
    :root, [data-bs-theme] {
        --bs-primary: #6366f1;
        --bs-primary-rgb: 99, 102, 241;
        --bs-link-color: #6366f1;
        --bs-link-hover-color: #4f46e5;
    }
    .btn-primary {
        --bs-btn-bg: #6366f1;
        --bs-btn-border-color: #6366f1;
        --bs-btn-hover-bg: #4f46e5;
        --bs-btn-hover-border-color: #4338ca;
        --bs-btn-active-bg: #4338ca;
        --bs-btn-active-border-color: #3730a3;
        --bs-btn-disabled-bg: #6366f1;
        --bs-btn-disabled-border-color: #6366f1;
        --bs-btn-focus-shadow-rgb: 99, 102, 241;
    }
    .btn-outline-primary {
        --bs-btn-color: #6366f1;
        --bs-btn-border-color: #6366f1;
        --bs-btn-hover-bg: #6366f1;
        --bs-btn-hover-border-color: #6366f1;
        --bs-btn-active-bg: #6366f1;
        --bs-btn-active-border-color: #6366f1;
        --bs-btn-focus-shadow-rgb: 99, 102, 241;
    }
</style>

<div
    x-data="{
        showColumnPanel: false,
        draggingCol: null,
        dragOverCol: null,
        confirm: { open: false, message: '', action: null },
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
    @live-table-ask-confirm.window="confirm.message = $event.detail.message; confirm.action = $event.detail.action; confirm.open = true"
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
    @include('live-table::partials.modal-confirm')

    {{-- Toast notifications --}}
    @include('live-table::partials.toasts')

</div>
