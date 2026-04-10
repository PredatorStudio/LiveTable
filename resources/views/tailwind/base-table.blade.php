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
        },
        startColResize(e, key) {
            e.preventDefault();
            const th = e.target.closest('th');
            const table = th.closest('table');
            const allCols = Array.from(table.querySelectorAll('col[data-col-key]'));
            const allThs = Array.from(th.closest('tr').querySelectorAll('th[data-col-key]'));
            // Freeze all th widths explicitly, clear col widths so th controls layout
            allThs.forEach(t => {
                t.style.width = Math.round(t.getBoundingClientRect().width) + 'px';
                t.style.minWidth = '';
                const c = allCols.find(x => x.dataset.colKey === t.dataset.colKey);
                if (c) { c.style.width = ''; c.style.minWidth = ''; }
            });
            const startX = e.clientX;
            const startW = Math.round(th.getBoundingClientRect().width);
            const startTableW = Math.round(table.getBoundingClientRect().width);
            table.style.width = startTableW + 'px';
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;cursor:col-resize;z-index:9999;';
            document.body.appendChild(overlay);
            const onMove = (ev) => {
                const w = Math.max(60, startW + ev.clientX - startX);
                th.style.width = w + 'px';
                table.style.width = (startTableW + w - startW) + 'px';
            };
            const onUp = (ev) => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                overlay.remove();
                const widths = {};
                allThs.forEach(t => {
                    const w = Math.round(t.getBoundingClientRect().width);
                    widths[t.dataset.colKey] = w;
                    const c = allCols.find(x => x.dataset.colKey === t.dataset.colKey);
                    if (c) c.style.width = w + 'px';
                });
                $wire.saveAllColumnWidths(widths);
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        }
    }"
    @live-table-ask-confirm.window="confirm.message = $event.detail.message; confirm.action = $event.detail.action; confirm.open = true"
    class="flex flex-col gap-3"
>

    {{-- Top bar --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
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

    <style>
        thead th { position: relative; overflow: visible !important; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
        .lt-resize-handle { position: absolute; right: 0; top: 0; bottom: 0; width: 5px; cursor: col-resize; z-index: 1; }
        .lt-resize-handle:hover { background: rgba(99,102,241,0.3); border-radius: 0 2px 2px 0; }
    </style>
</div>
