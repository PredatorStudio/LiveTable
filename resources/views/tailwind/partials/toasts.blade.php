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
    class="fixed bottom-4 right-4 z-[1100] flex flex-col gap-2"
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
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium shadow-lg min-w-[14rem]"
            :class="toast.type === 'danger' ? 'bg-red-600 text-white' : (toast.type === 'warning' ? 'bg-amber-500 text-white' : 'bg-green-600 text-white')"
            role="alert"
        >
            <span class="flex-1" x-text="toast.msg"></span>
            <button
                type="button"
                @click="remove(toast.id)"
                class="flex-shrink-0 opacity-75 hover:opacity-100 focus:outline-none transition-opacity"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </template>
</div>
