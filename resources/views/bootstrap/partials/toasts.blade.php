<div
    x-data="{
        toasts: [],
        add(msg, type) {
            const id = Date.now();
            this.toasts.push({ id, msg, type });
            setTimeout(() => this.remove(id), 3500);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @live-table-notify.window="add($event.detail.message, $event.detail.type ?? 'success')"
    class="position-fixed bottom-0 end-0 p-3"
    style="z-index: 1100;"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="toast show align-items-center mb-2 border-0"
            :class="toast.type === 'danger' ? 'text-bg-danger' : (toast.type === 'warning' ? 'text-bg-warning' : 'text-bg-success')"
            role="alert"
        >
            <div class="d-flex">
                <div class="toast-body small" x-text="toast.msg"></div>
                <button
                    type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    @click="remove(toast.id)"
                ></button>
            </div>
        </div>
    </template>
</div>
